<?php

require_once('com/moserv/sweat16/controller/controller.php');


class GotoShipController extends Controller {

	public function execute() {
		global $_SESSION;

		$params = $this->getInputParams();


		if (empty($_SESSION['customer-information'])) {
			$_SESSION['customer-information'] = array();
		}

		$_SESSION['customer-information']['email']		= $params['email'];
		$_SESSION['customer-information']['name']		= $params['name'];
		$_SESSION['customer-information']['sur-name']		= $params['sur-name'];
		$_SESSION['customer-information']['phone']		= $params['phone'];
		$_SESSION['customer-information']['address']		= $params['address'];
		$_SESSION['customer-information']['province-id']	= $params['province-id'];
		$_SESSION['customer-information']['province-name']	= $params['province-name'];

		$_SESSION['customer-information']['district-id']	= $params['district-id'];
		$_SESSION['customer-information']['district-name']	= $params['district-name'];
		$_SESSION['customer-information']['sub-district-id']	= $params['sub-district-id'];
		$_SESSION['customer-information']['sub-district-name']	= $params['sub-district-name'];
		$_SESSION['customer-information']['zip-code']		= $params['zip-code'];
		$_SESSION['customer-information']['save-address']	= (empty($params['save-address']))? 0: $params['save-address'];
	}
}

