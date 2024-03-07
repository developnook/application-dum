<?php

require_once('com/moserv/sweat16/page/page.php');
require_once('com/moserv/sweat16/loader/cart-loader.php');

class CheckoutPage extends Page {

	public function begin() {
		parent::begin();

		$paths = $this->getPaths();
		
		include_once("{$paths['conf']}/checkout-header.inc.php");
	}

	public function end() {
		$paths = $this->getPaths();

		include_once("{$paths['conf']}/checkout-footer.inc.php");

		parent::end();
	}

	protected function doLoad() {
		$session = $this->getSession();
		$capsule = parent::doLoad();

#		$loader = new CartLoader();
#		$capsule['cart'] = $loader->execute();
		$capsule['cart'] = $session->getVar('cart');

		return $capsule;
	}
}
