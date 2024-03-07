<?php

require_once('com/moserv/sweat16/servlet/servlet.php');
require_once('com/moserv/sweat16/controller/signon-controller.php');

class SignonServlet extends Servlet {

	public function execute() {
		global $_REQUEST;

		$controller = new SignonController();

		$controller->setInputParams($_POST);

		$controller->execute();

		$params = $controller->getOutputParams();

		if ($params['user-id'] == -1) {
			$this->redirect('/signon/invalid/');
		}
		elseif ($params['enabled'] == -1) {
			$this->redirect('/signon/no-activate/');
		}
		else {
			if (array_key_exists('url', $_REQUEST)) {
				header("location: {$_REQUEST['url']}");
				exit;
			}
			else
				$this->redirect('/home/');
		}
	}
}


