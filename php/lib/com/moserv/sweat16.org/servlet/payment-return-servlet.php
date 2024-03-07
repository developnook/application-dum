<?php

require_once('com/moserv/sweat16/servlet/servlet.php');
require_once('com/moserv/sweat16/controller/payment-return-controller.php');

class PaymentReturnServlet extends Servlet {

	public function execute() {
		global $_REQUEST;

		$controller = new PaymentReturnController();

		$controller->setInputParams($_REQUEST);

		$controller->execute();

		$params = $controller->getOutputParams();

		unset($_SESSION['cart']);
		unset($_SESSION['customer-information']);

		switch($_REQUEST['status']) {
			case 'CP':
			case 'TC':
				$this->redirect('/checkout/payment-success/');
			break;

			case 'RE':
			case 'PE':
			case 'VC':
			case 'VR':
			case 'PF':
				$this->redirect('/checkout/payment-failure/');
			break;
		}

	}
}

