<?php

require_once('com/moserv/sweat16/servlet/servlet.php');
require_once('com/moserv/sweat16/controller/goto-ship-controller.php');

class GotoShipServlet extends Servlet {

	public function execute() {
		global $_POST;

		$controller = new GotoShipController();

		$controller->setInputParams($_POST);

		$controller->execute();

		$params = $controller->getOutputParams();

		$this->redirect('/checkout/shipping-method/');

		exit;
	}
}

