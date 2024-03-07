<?php

require_once('com/moserv/sweat16/loader/purchase-order-loader.php');
require_once('com/moserv/sweat16/controller/controller.php');

class MailRespondTransferController extends Controller {

	protected function queryRespondTransfer($record) {
		$session = $this->getSession();
		
		$connection = $session->getConnection();

		$query = $connection->createQuery(
<<<sql
			select
				bat.bank_account_tran_id				as 'bank-account-tran-id',
				bat.payment_by_transfer_id				as 'payment-by-transfer-id',
				date_format(bat.tran_timestamp, '%Y-%m-%d %H:%i')	as 'sys-timestamp',
				be.bat_email_id						as 'bat-email-id',	
				po.purchase_order_id					as 'purchase-order-id',
				u.email
			from sweat16.bank_account_tran bat
				left join sweat16.bat_email be on be.bat_email_id = (
					select
						_be.bat_email_id
					from sweat16.bat_email _be
					where _be.bank_account_tran_id = bat.bank_account_tran_id
					order by be.bat_email_id desc
					limit 1
				)
				left join sweat16.payment_by_transfer pbt using (payment_by_transfer_id)
				left join sweat16.purchase_order po using (purchase_order_id)
				left join sweat16.cart c using (cart_id)
				left join sweat16.user u using (user_id)
			where bat.payment_by_transfer_id is not null
			and be.bat_email_id is null
			limit 1
sql
		);

		$query->open();

		$rows = $query->getResultArray();

		return $rows;
	}


	protected function sendEmail($record) {
		$session = $this->getSession();
		$mailer = $session->getMailer();
		$loader = new PurchaseOrderLoader();
		$capsule = $loader->execute(array('purchase-order-id' => $record['purchase-order-id']));

		$capsule['sys-timestamp'] = $record['sys-timestamp'];

		$paths = $this->getPaths();

		global $globalvar;

		$globalvar = $capsule;
		
		ob_start();
		include("{$paths['conf']}/mail-payment.inc.php");
		$body = ob_get_clean();

		$emailId = $mailer->execute(
			array(
				'type'		=> 5,
				'to'		=> $capsule['email'],
#				'to'		=> 'chitsakuns@gmail.com',
				'subject'	=> "ยืนยันการชำระเงิน {$capsule['purchase-order-code']}",
#				'subject'	=> "TEST ยืนยันการชำระเงิน {$capsule['purchase-order-code']}",
				'body'		=> $body
			)
		);

		return $emailId;
	}


	protected function newBatEmail($record) {
		$session = $this->getSession();
		$connection = $session->getConnection();

		$query = $connection->createQuery(
<<<sql
			insert into sweat16.bat_email (
				bank_account_tran_id,
				email_id
			)
			values (?, ?)
sql
		);

		$query->setInt(1, $record['bank-account-tran-id']);
		$query->setInt(2, $record['email-id']);

		$query->open();

		return $connection->lastId();
	}

	protected function updatePurchaseOrder($record) {
		$session = $this->getSession();
		$connection = $session->getConnection();

		$query = $connection->createQuery(
<<<sql
			update sweat16.purchase_order
				set status = 1
			where purchase_order_id = ?
			and status = 0
sql
		);

		$query->setInt(1, $record['purchase-order-id']);

		$query->open();
	}


	public function execute() {
		$session = $this->getSession();
		$mailer = $session->getMailer();

		$record = array();

		$record['rows'] = $this->queryRespondTransfer($record);

		foreach ($record['rows'] as &$row) {
			$row['email-id'] = $this->sendEmail(
				array(
					'purchase-order-id'	=> $row['purchase-order-id'],
					'sys-timestamp'		=> $row['sys-timestamp'],
					'email'			=> $row['email']
				)
			);

			$row['bat-email-id'] = $this->newBatEmail(
				array(
					'bank-account-tran-id'	=> $row['bank-account-tran-id'],
					'email-id'		=> $row['email-id']
				)
			);

			$this->updatePurchaseOrder(
				array(
					'purchase-order-id'	=> $row['purchase-order-id']
				)
			);
		}
		

		$this->setOutputParams($record);
	}
}



