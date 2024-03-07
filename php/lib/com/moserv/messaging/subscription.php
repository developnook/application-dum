<?php

require_once('com/moserv/net/session.php');

class SubscriptionFinder {

	const mask_inactive	= 0x00;
	const mask_active	= 0x01;

	private $session;
	private $msisdn;
	private $telcoId;
	private $rows;

	public function __construct($session) {
		$this->session = $session;
	}

	public function setMsisdn($msisdn) {
		$this->msisdn = $msisdn;
	}

	public function execute() {
		$connection = $this->session->getConnection();

		$query = $connection->createQuery('
			select
				subscriber_id,
				register_timestamp,
				unregister_timestamp,
				service_id,
				telco_id,
				enabled
			from message_service.subscriber
			where msisdn = ?
		');

		$query->setString(1, $this->msisdn);

		$query->open();

		$this->rows = $query->getResultArray();
	}

	public function getRows($mask = 0x02) {
		$rows = array();

		foreach ($this->rows as $row) {
			if ($mask & (1 << $row['enabled'])) {
				$rows[] = $row;
			}
		}

		return $rows;
	}

	public function findEnabledOne() {
		$index = 0;
		$found = false;

		while (!$found && $index < count($this->rows)) {
			$row = $this->rows[$index];

			if ($row['enabled'] == 1) {
				$found = true;
			}
			else {
				$index++;
			}
		}

		return ($found)? $index: -1;
	}

	public static function find($msisdn, $session = null) {
		if ($session == null) {
			$session = WapSession::$session;
		}

		$finder = new SubscriptionFinder($session);
		$finder->setMsisdn($msisdn);

		$finder->execute();

		return $finder->findEnabledOne();
	}
}

?>
