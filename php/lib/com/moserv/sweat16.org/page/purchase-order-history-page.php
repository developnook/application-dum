<?php

require_once('com/moserv/sweat16/page/page.php');
require_once('com/moserv/sweat16/loader/purchase-order-history-loader.php');

class PurchaseOrderHistoryPage extends Page {

	protected function doLoad() {
		$session = $this->getSession();

		$resultset = parent::doLoad();

		if ($session->isSignedOn()) {
			global $_REQUEST;

			$loader = new PurchaseOrderHistoryLoader();
			$resultset['purchase-order-history'] = $loader->execute($_REQUEST);

			return $resultset;
		}
		else
			$this->redirect('/signon/');
	}
}
