<?php

require_once('com/moserv/sweat16/loader/loader.php');


class BankListLoader extends Loader {

	protected function doExecute($params = null) {
		$session = $this->getSession();
		$connection = $session->getConnection();

		$query = $connection->createQuery(
<<<sql
			select
				bank_id as value,
				bank_name_th as title
			from sweat16.bank
			order by bank_name_th
sql
		);

		$query->open();

		$rows = $query->getResultArray();

		return $rows;
	}
}
