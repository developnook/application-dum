<?php

require_once('com/moserv/net/url.php');
require_once('com/moserv/sweat16/controller/controller.php');


class RedeemController extends Controller {

	protected function queryReceipt($record) {
		$session = $this->getSession();
		$connection = $session->getConnection();
		$query = $connection->createQuery(
<<<sql
			select
				rc.receipt_id		as 'receipt-id',
				coalesce(rd.user_id, 0) as 'user-id'
			from sweat16.receipt rc
				join sweat16.event_schedule es using (event_schedule_id)
				join sweat16.event e using (event_id)
				left join sweat16.redeem rd using (receipt_id)
			where rc.receipt_code = ?
			and rc.amount = ?
sql
		);

		$query->setString(1, $record['receipt-code']);
		$query->setFloat(2, $record['receipt-amount']);

		$query->open();

		$rows = $query->getResultArray();

		return (count($rows) == 0)? array(0, 0): array(
			$rows[0]['receipt-id'],
			$rows[0]['user-id']
		);
	}


	protected function newRedeem($record) {
		$session = $this->getSession();
		$connection = $session->getConnection();
		$query = $connection->createQuery(
<<<sql
			insert into sweat16.redeem (
				user_id,
				receipt_id
			)
			values (
				?,
				?
			)
sql
		);

		$query->setInt(1, $record['user-id']);
		$query->setInt(2, $record['receipt-id']);

		$query->open();

		return $connection->lastId();
	}

	public function execute() {
		$session = $this->getSession();
		$params = $this->getInputParams();

		$record = array();

		list($record['receipt-id'], $record['user-id']) = $this->queryReceipt(
			array(
				'receipt-code'		=> $params['receipt-code'],
				'receipt-amount'	=> $params['receipt-amount']
			)
		);

		if ($record['receipt-id'] != 0 && $record['user-id'] == 0) {
			$record['redeem-id'] = $this->newRedeem(
				array(
					'user-id'	=> $session->getVar('user-id'),
					'receipt-id'	=> $record['receipt-id']
				)
			);
		}

		$this->setOutputParams($record);
	}
}

