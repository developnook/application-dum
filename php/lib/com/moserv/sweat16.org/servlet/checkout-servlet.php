<?php

require_once('com/moserv/sweat16/servlet/servlet.php');
require_once('com/moserv/sweat16/controller/checkout-controller.php');

class CheckoutServlet extends Servlet {

	public function execute() {
		global $_POST;
		global $_SESSION;

		$controller = new CheckoutController();

		$controller->setInputParams($_POST);
		$controller->execute();
		$params = $controller->getOutputParams();

		unset($_SESSION['customer-information']);

		$this->redirect('/checkout/customer-information/');

		exit;
	}
}

