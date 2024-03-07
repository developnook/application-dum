<?php

require_once('com/moserv/sweat16/loader/purchase-order-loader.php');
require_once('com/moserv/sweat16/controller/controller.php');

class MailRespondPoShippingController extends Controller {

	protected function queryRespondPoShipping($record) {
		$session = $this->getSession();
		
		$connection = $session->getConnection();

		$query = $connection->createQuery(
<<<sql
			select
				ps.po_shipping_id			as 'po-shipping-id',
				date(ps.shipping_timestamp)		as 'sys-date',
				u.email,
				po.purchase_order_id			as 'purchase-order-id',
				ps.tracking_number			as 'tracking-number'
			from sweat16.purchase_order po
				join sweat16.cart c using (cart_id)
				join sweat16.user u using (user_id)
				join sweat16.po_shipping ps using (purchase_order_id)
				left join sweat16.po_shipping_email pse on pse.po_shipping_email_id = (
					select
						_pse.po_shipping_email_id
					from sweat16.po_shipping_email _pse
					where _pse.po_shipping_id = ps.po_shipping_id
					order by _pse.po_shipping_email_id desc
					limit 1
				)
			where po.sys_timestamp >= '2018-12-21 16:30:00'
			and po.status = 1
			and ps.tracking_number is not null
			and pse.po_shipping_email_id is null
			order by po.purchase_order_id
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

		$capsule['sys-date'] = $record['sys-date'];
		$capsule['tracking-number'] = $record['tracking-number'];

		$paths = $this->getPaths();

		global $globalvar;

		$globalvar = $capsule;
		
		ob_start();
		include("{$paths['conf']}/mail-po-shipping.inc.php");
		$body = ob_get_clean();

		$emailId = $mailer->execute(
			array(
				'type'		=> 7,
				'to'		=> $capsule['email'],
#				'to'		=> 'chitsakuns@gmail.com',
				'subject'	=> "ยืนยันการจัดส่งสินค้า {$capsule['purchase-order-code']}",
#				'subject'	=> "TEST ยืนยันการจัดส่งสินค้า {$capsule['purchase-order-code']} {$capsule['email']}",
				'body'		=> $body
			)
		);

		return $emailId;
	}


	protected function newPoShippingEmail($record) {
		$session = $this->getSession();
		$connection = $session->getConnection();

		$query = $connection->createQuery(
<<<sql
			insert into sweat16.po_shipping_email (
				po_shipping_id,
				email_id
			)
			values (?, ?)
sql
		);

		$query->setInt(1, $record['po-shipping-id']);
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
				set status = 2
			where purchase_order_id = ?
			and status = 1
sql
		);

		$query->setInt(1, $record['purchase-order-id']);

		$query->open();
	}


	public function execute() {
		$session = $this->getSession();
		$mailer = $session->getMailer();

		$record = array();

		$record['rows'] = $this->queryRespondPoShipping($record);

		foreach ($record['rows'] as &$row) {
			echo "<br />";
			print_r($row);
			echo "<br />";


			$row['email-id'] = $this->sendEmail(
				array(
					'purchase-order-id'	=> $row['purchase-order-id'],
					'sys-date'		=> $row['sys-date'],
					'tracking-number'	=> $row['tracking-number'],
					'email'			=> $row['email']
				)
			);

			$row['po-shipping-email-id'] = $this->newPoShippingEmail(
				array(
					'po-shipping-id'	=> $row['po-shipping-id'],
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

