<?php

require_once('com/moserv/sweat16/servlet/servlet.php');
require_once('com/moserv/sweat16/controller/product-add-controller.php');

class ProductAddServlet extends Servlet {

	public function execute() {
		global $_REQUEST;

		$controller = new ProductAddController();

		$controller->setInputParams($_POST);

		$controller->execute();

		$params = $controller->getOutputParams();

		if ($params == null) {
			$this->redirect('/signon/');
		}
		else {
			$this->redirect('/cart/');
		}
	}
}


