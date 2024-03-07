<?php

require_once('com/moserv/sweat16/servlet/servlet.php');
require_once('com/moserv/sweat16/controller/signup-controller.php');

class SignupServlet extends Servlet {

	public function execute() {
		global $_REQUEST;

		$controller = new SignupController();

		$controller->setInputParams($_REQUEST);

		$controller->execute();

		$params = $controller->getOutputParams();


		switch ($params['enabled']) {

			case -1:
				$this->redirect('/signup/activate');
			break;

			case 1:
				$this->redirect('/signup/exist');
			break;

			default:
				$this->redirect('/signup/unknown');
			break;
		}

		exit;
	}
}


