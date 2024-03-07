<?php

require_once('com/moserv/sweat16/controller/controller.php');


class PaymentPostbackController extends Controller {

	protected function newPaymentPostback($record) {
		$session = $this->getSession();

		$connection = $session->getConnection();

		$query = $connection->createQuery(
<<<sql
			insert into sweat16.pbc_postback (
				payment_by_card_id,
				header,
				data
			)
			values (?, ?, ?)
sql
		);

		$query->setInt(1, $record['payment-by-card-id']);
		$query->setString(2, $record['header']);
		$query->setString(3, $record['data']);

		$query->open();

		return $connection->lastId();
	}


	public function execute() {
		global $_SERVER;

		$params = $this->getInputParams();
		$record = array();

		$record['pbc-postback-id'] = $this->newPaymentPostback(
			array(
				'payment-by-card-id'	=> $params['refno'],
				'header'		=> json_encode($_SERVER),
				'data'			=> json_encode($params)
			)
		);

		$this->setOutputParams($record);
	}
}

