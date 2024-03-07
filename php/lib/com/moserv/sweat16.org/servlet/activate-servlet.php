<?php

require_once('com/moserv/sweat16/servlet/servlet.php');
require_once('com/moserv/sweat16/controller/activate-controller.php');

class ActivateServlet extends Servlet {

	public function execute() {
		global $_REQUEST;

		$controller = new ActivateController();

		$controller->setInputParams($_REQUEST);

		$controller->execute();

		$params = $controller->getOutputParams();

		$this->redirect('/signup/activated/');
	}
}


