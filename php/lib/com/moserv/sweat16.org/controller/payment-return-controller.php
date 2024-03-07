<?php

require_once('com/moserv/sweat16/controller/controller.php');
require_once('com/moserv/sweat16/loader/purchase-order-loader.php');


class PaymentReturnController extends Controller {

	protected function newPaymentReturn($record) {
		$session = $this->getSession();

		$connection = $session->getConnection();

		$query = $connection->createQuery(
<<<sql
			insert into sweat16.pbc_return (
				payment_by_card_id,
				status_code
			)
			values (?, ?)
sql
		);

		$query->setInt(1, $record['payment-by-card-id']);
		$query->setString(2, $record['status-code']);

		$query->open();

		return $connection->lastId();
	}

	protected function updatePurchaseOrder($record) {
		if ($record['status-code'] != 'CP' && $record['status-code'] != 'TC') {
			return 0;
		}

		$session = $this->getSession();
		$connection = $session->getConnection();

		$query = $connection->createQuery(
<<<sql
			update sweat16.cart c
				join sweat16.purchase_order po using (cart_id)
				join sweat16.payment_by_card pbc using (purchase_order_id)
			set
				c.checked_out = 1,
				po.status = 1,
				pbc.status = 1
			where pbc.payment_by_card_id = ?
sql
		);

		$query->setInt(1, $record['payment-by-card-id']);

		$query->open();

		return 1;
	}

	protected function getPurchaseOrder($record) {
		if ($record['status-code'] != 'CP' && $record['status-code'] != 'TC') {
			return 0;
		}

		$session = $this->getSession();

		$connection = $session->getConnection();

		$query = $connection->createQuery(
<<<sql
			select
				po.purchase_order_id as 'purchase-order-id'
			from sweat16.payment_by_card pbc
				join sweat16.purchase_order po using (purchase_order_id)
			where pbc.payment_by_card_id = ?
sql
		);

		$query->setInt(1, $record['payment-by-card-id']);

		$query->open();
		$rows = $query->getResultArray();

		return (count($rows) == 0)? 0: $rows[0]['purchase-order-id'];
	}

	protected function sendMail($record) {
		if ($record['status-code'] != 'CP' && $record['status-code'] != 'TC') {
			return 0;
		}

		$loader = new PurchaseOrderLoader();

		$capsule = $loader->execute(array('purchase-order-id' => $record['purchase-order-id']));

		$session = $this->getSession();
		$mailer = $session->getMailer();

		$paths = $this->getPaths();

		global $globalvar;

		$globalvar = $capsule;
		
		ob_start();
		include("{$paths['conf']}/mail-payment.inc.php");
		$body = ob_get_clean();

		$emailId = $mailer->execute(
			array(
				'type'		=> 6,
				'to'		=> $capsule['email'],
				'subject'	=> "ยืนยันการชำระเงิน {$capsule['purchase-order-code']}",
				'body'		=> $body
			)
		);

		return $emailId;
	}

	protected function newPbcEmail($record) {
		$session = $this->getSession();
		$connection = $session->getConnection();

		$query = $connection->createQuery(
<<<sql
			insert into sweat16.pbc_email (
				payment_by_card_id,
				email_id
			)
			values (?, ?)
sql
		);

		$query->setInt(1, $record['payment-by-card-id']);
		$query->setInt(2, $record['email-id']);

		$query->open();

		return $connection->lastId();
	}


	public function execute() {
		global $_SERVER;

		$params = $this->getInputParams();
		$record = array();

		$record['pbc-return-id'] = $this->newPaymentReturn(
			array(
				'payment-by-card-id'	=> $params['refno'],
				'status-code'		=> $params['status']
			)
		);

		$record['po-status'] = $this->updatePurchaseOrder(
			array(
				'payment-by-card-id'	=> $params['refno'],
				'status-code'		=> $params['status']
			)
		);

		$record['purchase-order-id'] = $this->getPurchaseOrder(
			array(
				'payment-by-card-id'	=> $params['refno'],
				'status-code'		=> $params['status']
			)
		);

		$record['email-id'] = $this->sendMail(
			array(
				'payment-by-card-id'	=> $params['refno'],
				'status-code'		=> $params['status'],
				'purchase-order-id'	=> $record['purchase-order-id']
			)
		);

		if ($record['email-id'] != 0) {
			$record['pbc-email-id'] = $this->newPbcEmail(
				array(
					'payment-by-card-id'	=> $params['refno'],
					'email-id'		=> $record['email-id']
				)
			);
		}

		$this->setOutputParams($record);
	}
}

