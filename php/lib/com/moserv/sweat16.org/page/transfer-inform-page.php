<?php

require_once('com/moserv/sweat16/page/page.php');
require_once('com/moserv/sweat16/loader/bank-list-loader.php');
require_once('com/moserv/sweat16/loader/payment-account-list-loader.php');
require_once('com/moserv/sweat16/loader/purchase-order-list-loader.php');

class TransferInformPage extends Page {

	protected function doLoad() {
		$session = $this->getSession();

		if ($session->isSignedOn()) {
			global $_REQUEST;

			$record = parent::doLoad();

			$bankListLoader = new BankListLoader();
			$record['bank-list'] = $bankListLoader->execute($_REQUEST);

			$paymentAccountListLoader = new PaymentAccountListLoader();
			$record['payment-account-list'] = $paymentAccountListLoader->execute($_REQUEST);

			$purchaseOrderListLoader = new PurchaseOrderListLoader();
			$record['purchase-order-list'] = $purchaseOrderListLoader->execute($_REQUEST);

			return $record;	
		} 
		else
			$this->redirect('/signon/', true);
	}
}
