<?php

require_once('com/moserv/sweat16/servlet/servlet.php');
require_once('com/moserv/sweat16/controller/signoff-controller.php');

class SignoffServlet extends Servlet {

	public function execute() {
		global $_REQUEST;

		$controller = new SignoffController();

		$controller->setInputParams($_POST);

		$controller->execute();

		$this->redirect('/home/');
	}
}


