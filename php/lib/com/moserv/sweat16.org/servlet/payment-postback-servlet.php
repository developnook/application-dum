<?php

require_once('com/moserv/sweat16/servlet/servlet.php');
require_once('com/moserv/sweat16/controller/payment-postback-controller.php');

class PaymentPostbackServlet extends Servlet {

	public function execute() {
		global $_REQUEST;

		$controller = new PaymentPostbackController();

		$controller->setInputParams($_REQUEST);

		$controller->execute();

		$params = $controller->getOutputParams();

		header('Content-Type: application/json; charset=utf-8');

		echo json_encode(array('status' => 'ok'));
	}
}


