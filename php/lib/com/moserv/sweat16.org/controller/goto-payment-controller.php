<?php

require_once('com/moserv/sweat16/controller/controller.php');


class GotoPaymentController extends Controller {

	public function execute() {
		global $_SESSION;

		$params = $this->getInputParams();


		if (empty($_SESSION['customer-information'])) {
			$_SESSION['customer-information'] = array();
		}

		$_SESSION['customer-information']['shipping-email']	= $params['shipping-email'];
		$_SESSION['customer-information']['shipping-address']	= $params['shipping-address'];
		$_SESSION['customer-information']['shipping-price']	= $params['shipping-price'];
		$_SESSION['customer-information']['shipping-method']	= $params['shipping-method'];

	}
}

