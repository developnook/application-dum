<?php

require_once('com/moserv/sweat16/servlet/servlet.php');
require_once('com/moserv/sweat16/controller/password-apply-controller.php');

class PasswordApplyServlet extends Servlet {

	public function execute() {
		global $_REQUEST;
		global $_SESSION;

		if (empty($_SESSION['password-reset-apply-id'])) {
			$this->redirect('/home/');
			exit;
		}
		else {

			$controller = new PasswordApplyController();

			$controller->setInputParams($_REQUEST);

			$controller->execute();

			$params = $controller->getOutputParams();


			unset($_SESSION['password-reset-apply-id']);
			$this->redirect('/password/applied/');
		}
	}
}

