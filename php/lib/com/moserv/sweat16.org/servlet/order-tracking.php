<?php

require_once('com/moserv/net/url.php');
require_once('com/moserv/sweat16/servlet/servlet.php');
require_once('com/moserv/sweat16/controller/order-tracking-controller.php');

class TrackingServlet extends Servlet {


	public function execute() {
		global $_REQUEST;


		$controller = new OrderTrackingController();
		
		$controller->setInputParams($_REQUEST);

		$controller->execute();

		$params = $controller->getOutputParams();

		if (empty($params['url'])) {
			$params['url'] = '/purchase-order-history/po-tracking/tracking-notfound/';
		}

		$url = new Url($params['url']);
		$url->redirect();
	}
}

