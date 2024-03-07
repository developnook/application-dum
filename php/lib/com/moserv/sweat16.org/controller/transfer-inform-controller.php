<?php

require_once('com/moserv/sweat16/controller/controller.php');


class TransferInformController extends Controller {


	protected function updatePaymentByTransfer($record) {
		$session = $this->getSession();
		$connection = $session->getConnection();

		$query = $connection->createQuery(
<<<sql
			insert into sweat16.payment_by_transfer (
				purchase_order_id,
				payment_account_id,
				bank_id,
				year,
				month,
				day,
				hour,
				minute,
				slip,
				filename
			)
			values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
sql
		);

		$query->setInt(1, $record['purchase-order-id']);
		$query->setInt(2, $record['payment-account-id']);
		$query->setInt(3, $record['bank-id']);
		$query->setInt(4, $record['year']);
		$query->setInt(5, $record['month']);
		$query->setInt(6, $record['day']);
		$query->setInt(7, $record['hour']);
		$query->setInt(8, $record['minute']);
		$query->setString(9, $record['slip']);
		$query->setString(10, $record['filename']);

		$query->open();


		return $connection->lastId();
	}

	protected function queryPurchaseOrder($record) {
		$session = $this->getSession();
		$connection = $session->getConnection();

		$query = $connection->createQuery(
<<<sql
			select
				po_code as 'po-code'
			from sweat16.purchase_order
			where purchase_order_id = ?
sql
		);

		$query->setInt(1, $record['purchase-order-id']);

		$query->open();
		$rows = $query->getResultArray();

		return (count($rows) == 0)? array(): array($rows[0]['po-code']);
	}

	protected function sendMail($record) {
		global $_SESSION;
		$session = $this->getSession();
		$mailer = $session->getMailer();

		global $globalvar;

		$globalvar = $record;

		$html =
<<<html
			<p>ผู้แจ้งโอน: {$_SESSION['user-email']}</p>
			<p>รหัสสั่งซื้อ: {$record['po-code']}</p>
			<p>เวลาโอน: {$record['year']}-{$record['month']}-{$record['day']} {$record['hour']}:{$record['minute']}</p>
			<p>รหัสการ transfer: {$record['payment-by-transfer-id']}</p>
html
		;

		$mailer->execute(
			array(
				'type'		=> 0,
				'to'		=> 'sweat16.mail@moserv.co.th',
				'subject'	=> "มีการแจ้งโอนโดย {$_SESSION['user-email']}",
				'body'		=> $html,
				'attachments'	=> array(
					array(
						'filename'	=> $record['filename'],
						'content'	=> base64_encode($record['slip']),
						'encoding'	=> 'base64'
					)
				)
			)
		);
	}

	public function execute() {
		global $_FILES;
		$session = $this->getSession();
		$params = $this->getInputParams();

		if (!$session->isSignedOn()) {
			$this->setOutputParams(null);
			return;
		}

		$record = array();

		$data		= file_get_contents($_FILES['slip']['tmp_name']);
		$filename	= $_FILES['slip']['name'];

		if (isset($_FILES['slip'])) {

#			echo $_FILES['slip']['size'];
#			exit;


			$record['payment-by-transfer-id'] = $this->updatePaymentByTransfer(
				array(
					'purchase-order-id'	=> $params['purchase-order-id'],
					'payment-account-id'	=> $params['payment-account-id'],
					'bank-id'		=> $params['bank-id'],
					'year'			=> $params['year'],
					'month'			=> $params['month'],
					'day'			=> $params['day'],
					'hour'			=> $params['hour'],
					'minute'		=> $params['minute'],
					'slip'			=> $data,
					'filename'		=> $filename
				)
			);

			list($record['po-code']) = $this->queryPurchaseOrder(array('purchase-order-id' => $params['purchase-order-id']));


			$this->sendMail(
				array(
					'payment-by-transfer-id'	=> $record['payment-by-transfer-id'],
					'purchase-order-id'		=> $params['purchase-order-id'],
#					'po-code'			=> sprintf('SWT%05d', $params['purchase-order-id']),
					'po-code'			=> $record['po-code'],
					'bank-id'			=> $params['bank-id'],
					'year'				=> sprintf('%04d', $params['year']),
					'month'				=> sprintf('%02d', $params['month']),
					'day'				=> sprintf('%02d', $params['day']),
					'hour'				=> sprintf('%02d', $params['hour']),
					'minute'			=> sprintf('%02d', $params['minute']),
					'slip'				=> $data,
					'filename'			=> $filename
				)
			);
		}

		$this->setOutputParams($record);
	}
}

