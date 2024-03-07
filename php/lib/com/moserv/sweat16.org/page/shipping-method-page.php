<?php

require_once('com/moserv/sweat16/page/checkout-page.php');
require_once('com/moserv/sweat16/loader/shipping-list-loader.php');

class ShippingMethodPage extends CheckoutPage {

	protected function doLoad() {
		$session = $this->getSession();
		$capsule = parent::doLoad();

		$loader = new ShippingListLoader();
		$capsule['shipping-list'] = $loader->execute(array('user-id' => $session->getVar('user-id')));

		return $capsule;
	}
}
