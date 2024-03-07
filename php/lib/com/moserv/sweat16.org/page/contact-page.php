<?php

require_once('com/moserv/sweat16/page/page.php');
require_once('com/moserv/sweat16/loader/purchase-order-list-loader.php');

class ContactPage extends Page {

	protected function doLoad() {
		$session = $this->getSession();

		if ($session->isSignedOn()) {
			global $_REQUEST;

			$record = parent::doLoad();

			$purchaseOrderListLoader = new PurchaseOrderListLoader();
			$record['purchase-order-list'] = $purchaseOrderListLoader->execute($_REQUEST);

			return $record;
		}
		else
			$this->redirect('/signon/');
	}
}
