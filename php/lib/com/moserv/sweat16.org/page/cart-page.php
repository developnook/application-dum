<?php

require_once('com/moserv/sweat16/page/page.php');
require_once('com/moserv/sweat16/loader/cart-loader.php');

class CartPage extends Page {

	protected function doLoad() {
		$session = $this->getSession();

		if ($session->isSignedOn()) {
			global $_REQUEST;

			$record = parent::doLoad();
			$loader = CartLoader::newInstance();

			$record = array_merge(
				$record,
				array('cart' => $loader->execute($_REQUEST))
			);

			return $record;
		}
		else
			$this->redirect('/signon/');
	}
}
