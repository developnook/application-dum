<?php

require_once('com/moserv/sweat16/loader/loader.php');


class PaymentAccountListLoader extends Loader {

	protected function doExecute($params = null) {
		$session = $this->getSession();
		$connection = $session->getConnection();

		$query = $connection->createQuery(
<<<sql
			select
				pa.payment_account_id as value,
				pa.account_name as 'account-name',
				concat(
					replace(b.bank_name_th, 'ธนาคาร', 'ธ.'), ' [',
					substr(pa.account_no, 1, 3), '-',
					substr(pa.account_no, 4, 1), '-',
					substr(pa.account_no, 5, 5), '-',
					substr(pa.account_no, 10, 5), '] ',
					pa.account_name
				) as title
			from sweat16.payment_account pa
				join bank b using (bank_id)
			where pa.enabled = 1
			order by b.bank_name_th, pa.account_no
sql
		);

		$query->open();

		$rows = $query->getResultArray();

		return $rows;
	}
}
