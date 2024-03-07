<?php

require_once('com/moserv/sweat16/servlet/servlet.php');
require_once('com/moserv/sweat16/controller/cart-delete-controller.php');

class CartDeleteServlet extends Servlet {

	public function execute() {
		global $_REQUEST;

		$controller = new CartDeleteController();

		$controller->setInputParams($_REQUEST);

		$controller->execute();

		$params = $controller->getOutputParams();

		header('content-type: application/json; charset=utf-8');

		echo json_encode($params);
	}
}


