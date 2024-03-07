<?php

require_once('com/moserv/sweat16/servlet/servlet.php');
require_once('com/moserv/sweat16/controller/password-request-controller.php');

class PasswordRequestServlet extends Servlet {

	public function execute() {
		global $_REQUEST;

		$controller = new PasswordRequestController();

		$controller->setInputParams($_POST);

		$controller->execute();

		$params = $controller->getOutputParams();


		if ($params['user-id'] == 0) {
			$this->redirect('/password/no-exist/');
		}
		elseif ($params['enabled'] == -1) {
			$this->redirect('/password/no-activate/');
		}
		elseif (empty($params['reset-key'])) {
			$this->redirect('/password/no-timeout/');
		}
		else {
			$this->redirect('/password/requested/');
		}
	}
}

