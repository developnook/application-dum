<?php

require_once('com/moserv/net/url.php');
require_once('com/moserv/sweat16/servlet/servlet.php');
require_once('com/moserv/sweat16/loader/order-tracking-loader.php');

class OrderTrackingServlet extends Servlet {


	public function execute() {
		global $_REQUEST;


		$loader = new OrderTrackingLoader();
		
		$rows = $loader->execute($_REQUEST);

		$url = null;

		if (empty($rows) || count($rows) == 0 || empty($rows[0]['tracking-url'])) {
			$url = new Url();
			$url->setPath('/purchase-order-history/po-tracking/tracking-notfound/');
			$url->setParams(null);
		}
		else {
			$url = new Url($rows[0]['tracking-url']);
		}

		$url->redirect();
	}
}

