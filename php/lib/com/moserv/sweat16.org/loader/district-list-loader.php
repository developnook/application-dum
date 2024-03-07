<?php

require_once('com/moserv/sweat16/loader/loader.php');


class DistrictListLoader extends Loader {


	protected function doExecute($params = null) {
		if ($params == null || empty($params['district-id']) || $params['district-id'] == 0) {
			return array();
		}

		$session = $this->getSession();
		$connection = $session->getConnection();

		$query = $connection->createQuery(
<<<sql
			select
				d2.province_id,
				d2.district_id,
				d2.district_name_th as district_name,
				(d1.district_id = d2.district_id) as checked
			from sweat16.district d1
				join sweat16.district d2 using (province_id)
			where d1.district_id = ?
			and d2.enabled = 1
			order by district_name
sql
		);

		$query->setInt(1, $params['district-id']);

		$query->open();

		$rows = $query->getResultArray();

		
		return $rows;
	}
}
