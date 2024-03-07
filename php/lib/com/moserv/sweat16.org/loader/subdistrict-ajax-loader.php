<?php

require_once('com/moserv/sweat16/loader/loader.php');


class SubdistrictAjaxLoader extends Loader {


	protected function doExecute($params = null) {

		$session = $this->getSession();
		$connection = $session->getConnection();

		$query = $connection->createQuery(
<<<sql
			select
				subdistrict_id,
				subdistrict_name_th as subdistrict_name
			from sweat16.subdistrict
			where district_id = ?
			and enabled = 1
			order by subdistrict_name
sql
		);

		$query->setInt(1, $params['district-id']);

		$query->open();

		$rows = $query->getResultArray();

		
		return $rows;
	}
}
