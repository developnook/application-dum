<?php

require_once('com/moserv/log/logger.php');
require_once('com/moserv/net/url.php');
require_once('com/moserv/sql/connection.php');
require_once('com/moserv/util/web.php');

class Queue {

	private $connection;
	private $queueName;

	public function __construct() {

		$base = web::getCfgVar('qbase');
		$host = web::getCfgVar('qhost');
		$port = web::getCfgVar('qport');
		$user = web::getCfgVar('quser');
		$pass = web::getCfgVar('qpass');

		$this->database = new MySql($qbase, $qhost, $qport, $quser, $qpass);
		$this->connection = new Connection($this->database, false);

	}


	public function setQueueName($queueName) {
		$this->queueName = $queueName;
	}


	public function produce($array) {

#		$array['message_id'] = ;
#		$array['sys_timestamp'] = ;
#		$array['cycle'] =
#		$array['source_id'] =
#		$array['proxy_id'] =
#		$array['proxy_message_id'] =


		$queueName = $this->queueName;

		$columns = array_keys($array);
		$columnSet = implode(', ', $columns);

		$values = array_fill(0, count($columns), '?');
		$valueSet = implode(', ', $values);

		$sql = "insert into {$queueName} ({$columnSet}) values ({$valueSet})";
		$query = $this->connection->createQuery($sql);

		for ($index = 0; $index < count($columns); $index++) {
			$key = $columns[$index];
			$value = $array[$key];

			switch (gettype($value)) {
				case 'integer':
					$query->setInt($index + 1, $value);
				break;

				case 'double':
				case 'float':
					$query->setFloat($index + 1, $value);
				break;

				default:
					$query->setString($index + 1, $value);
				break;
			}
		}

		$query->open();
	}

	
}


?>
