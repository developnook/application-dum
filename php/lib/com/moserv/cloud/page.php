<?php

require_once('com/moserv/net/session.php');
require_once('com/moserv/sql/connection.php');


class CloudSession extends Session {

	public function __construct() {
		session_start();
	}


	public function save() {
		
	}
}

