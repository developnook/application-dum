<?php

require_once('com/moserv/sweat16/servlet/servlet.php');
require_once('com/moserv/sweat16/controller/goto-payment-controller.php');

class GotoPaymentServlet extends Servlet {

	public function execute() {
		global $_POST;

		$controller = new GotoPaymentController();

		$controller->setInputParams($_POST);

		$controller->execute();

		$params = $controller->getOutputParams();

		$this->redirect('/checkout/payment-method/');

		exit;
	}
}

