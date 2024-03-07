<?php
	require_once('com/moserv/sql/connection.sql');


class Loader {

	const SFLD_TIME		= 1;
	const SFLD_GROUP	= 2;
	const SFLD_NAME		= 3;

	const SORT_DSC		= 0;
	const SORT_ASC		= 1;

	public static $connection = null;


	public static function initConnection() {
		if (Loader::$connection == null) {
			$base = get_cfg_var('com.moserv.wap.base');
			$host = get_cfg_var('com.moserv.wap.host');
			$port = get_cfg_var('com.moserv.wap.port');
			$user = get_cfg_var('com.moserv.wap.user');
			$pass = get_cfg_var('com.moserv.wap.pass');

			$database = new MySql($base, $host, $port, $user, $pass);

			Loader::$connection = new Connection($database);
		}

		return Loader::$connection;
	}


	public static function loadServiceCounter() {
		$connection = Loader::InitConnection();

		$query = $connection->createQuery('select count(*) as counter from message_service.service');

		$query->open();

		$result = $query->getResultArray();

		return $result[0]['counter'];
	}


	public static function loadServices($page, $items, $sfield = Loader::SFLD_TIME, $stype = Loader::SORT_DSC) {

		$connection = Loader::initConnection();

		$query = $connection->createQuery('
			select
				s.created_timestamp,
				g.service_group_name,
				
			from message_service.service s
				join message_service.service_group g using (service_group_id)
				join message_service.incoming_channel i using (service_id)
				join message_service.
			order by ?
		');

		$query->open();

		$result = $query->getResultArray();

		return $result[0]['counter'];

	}
}
?>
