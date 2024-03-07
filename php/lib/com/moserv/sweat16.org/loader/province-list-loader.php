<?php

require_once('com/moserv/sweat16/loader/loader.php');


class ProvinceListLoader extends Loader {


	protected function doExecute($params = null) {
		$session = $this->getSession();
		$connection = $session->getConnection();

		$query = $connection->createQuery(
<<<sql
			select
				province_id,
				province_name_th as province_name,
				(province_id = ?) as checked
			from sweat16.province
			where enabled = 1
			order by province_name
sql
		);

		$query->setInt(1, (empty($params['province-id']))? 0: $params['province-id']);

		$query->open();

		$rows = $query->getResultArray();
		
		return $rows;
	}
}
