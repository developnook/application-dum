<?php

require_once('com/moserv/sweat16/servlet/servlet.php');
require_once('com/moserv/sweat16/controller/password-reset-controller.php');

class PasswordResetServlet extends Servlet {

	public function execute() {
		global $_REQUEST;
		global $_SESSION;

		$controller = new PasswordResetController();

		$controller->setInputParams($_REQUEST);

		$controller->execute();

		$params = $controller->getOutputParams();


		if ($params['password-reset-id'] == 0) {
			$this->redirect('/password/reset-nokey/');
		}
		elseif ($params['applied'] == 1) {
			$this->redirect('/password/reset-already-applied/');
		}
		elseif ($params['expired'] == 1) {
			$this->redirect('/password/reset-already-expired/');
		}
		else {
			$_SESSION['password-reset-apply-id'] = $params['password-reset-apply-id'];
			$this->redirect('/password/apply/');
		}
	}
}

