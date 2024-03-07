<?php

require_once('com/moserv/sweat16/controller/controller.php');


class PaymentRequestController extends Controller {

	protected function newPaymentRequest($record) {
		$session = $this->getSession();

		$connection = $session->getConnection();

		$query = $connection->createQuery(
<<<sql
			insert into sweat16.pbc_request (
				payment_by_card_id,
				params
			)
			values (?, ?)
sql
		);

		$query->setInt(1, $record['payment-by-card-id']);
		$query->setString(2, $record['params']);

		$query->open();

		return $connection->lastId();
	}


	public function execute() {
		global $_SERVER;

		$params = $this->getInputParams();
		$record = array();

		$record['pbc-request-id'] = $this->newPaymentRequest($params);

		$this->setOutputParams($record);
	}
}

