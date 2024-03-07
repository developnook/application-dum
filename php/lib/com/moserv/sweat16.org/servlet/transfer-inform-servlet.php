<?php

require_once('com/moserv/sweat16/servlet/servlet.php');
require_once('com/moserv/sweat16/controller/transfer-inform-controller.php');

class TransferInformServlet extends Servlet {

	public function execute() {
		global $_REQUEST;
		global $_SESSION;

		$controller = new TransferInformController();

		$controller->setInputParams($_POST);

		$controller->execute();

		$params = $controller->getOutputParams();

		$_SESSION['last-purchase-order-id'] = $_POST['purchase-order-id'];
		$_SESSION['last-po-code'] = $params['po-code'];

		$this->redirect('/transfer-inform/informed/');
	}
}


