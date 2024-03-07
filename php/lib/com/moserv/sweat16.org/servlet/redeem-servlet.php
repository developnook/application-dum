<?php

require_once('com/moserv/sweat16/servlet/servlet.php');
require_once('com/moserv/sweat16/controller/redeem-controller.php');

class RedeemServlet extends Servlet {

	public function execute() {
		global $_REQUEST;

		$controller = new RedeemController();

		$controller->setInputParams($_POST);

		$controller->execute();

		$params = $controller->getOutputParams();

		$url = new Url();

		if ($params['receipt-id'] == 0) {
			# invalid receipt information
			$url->setPath('/redeem/invalid/');
		}
		elseif ($params['user-id'] != 0) {
			# this receipt has been redeemed
			$url->setPath('/redeem/redeemed-already/');
		}
		else {
			# thank you for your redeem
			$url->setPath('/redeem/thanks/');	
		}

		$url->redirect();
	}
}

