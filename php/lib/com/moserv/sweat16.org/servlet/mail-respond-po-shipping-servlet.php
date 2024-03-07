<?php

require_once('com/moserv/sweat16/servlet/servlet.php');
require_once('com/moserv/sweat16/controller/mail-respond-po-shipping-controller.php');


class MailRespondPoShippingServlet extends Servlet {

	public function execute() {
		global $_REQUEST;

		$controller = new MailRespondPoShippingController();

		$controller->setInputParams($_POST);

		$controller->execute();

		$params = $controller->getOutputParams();

		print_r($params);
	}
}
