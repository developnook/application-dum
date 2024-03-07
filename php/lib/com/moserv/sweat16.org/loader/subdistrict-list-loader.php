<?php

require_once('com/moserv/sweat16/loader/loader.php');


class SubdistrictListLoader extends Loader {


	protected function doExecute($params = null) {
		if ($params == null || empty($params['subdistrict-id']) || $params['subdistrict-id'] == 0) {
			return array();
		}

		$session = $this->getSession();
		$connection = $session->getConnection();

		$query = $connection->createQuery(
<<<sql
			select
				s2.district_id,
				s2.subdistrict_id,
				s2.subdistrict_name_th as subdistrict_name,
				(s1.subdistrict_id = s2.subdistrict_id) as checked
			from sweat16.subdistrict s1
				join sweat16.subdistrict s2 using (district_id)
			where s1.subdistrict_id = ?
			and s2.enabled = 1
			order by subdistrict_name
sql
		);

		$query->setInt(1, $params['subdistrict-id']);

		$query->open();

		$rows = $query->getResultArray();

		
		return $rows;
	}
}
