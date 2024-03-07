<?php

require_once('com/moserv/sweat16/servlet/servlet.php');
require_once('com/moserv/sweat16/controller/contact-controller.php');

class ContactServlet extends Servlet {

	public function execute() {
		global $_REQUEST;
		global $_SESSION;

		$controller = new ContactController();

		$controller->setInputParams($_POST);

		$controller->execute();

		$params = $controller->getOutputParams();

		$_SESSION['last-purchase-order-id'] = $_REQUEST['purchase-order-id'];
		$_SESSION['last-po-code'] = $params['po-code'];

		$this->redirect('/contact/contacted/');
	}
}

