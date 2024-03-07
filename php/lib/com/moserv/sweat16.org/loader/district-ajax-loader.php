<?php

require_once('com/moserv/sweat16/loader/loader.php');


class DistrictAjaxLoader extends Loader {


	protected function doExecute($params = null) {

		$session = $this->getSession();
		$connection = $session->getConnection();

		$query = $connection->createQuery(
<<<sql
			select
				district_id,
				district_name_th as district_name
			from sweat16.district
			where province_id = ?
			and enabled = 1
			order by district_name
sql
		);

		$query->setInt(1, $params['province-id']);

		$query->open();

		$rows = $query->getResultArray();

		
		return $rows;
	}
}
