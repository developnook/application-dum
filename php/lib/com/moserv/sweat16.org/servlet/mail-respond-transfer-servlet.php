<?php

require_once('com/moserv/sweat16/servlet/servlet.php');
require_once('com/moserv/sweat16/controller/mail-respond-transfer-controller.php');


class MailRespondTransferServlet extends Servlet {

	public function execute() {
		global $_REQUEST;

		$controller = new MailRespondTransferController();

		$controller->setInputParams($_POST);

		$controller->execute();

		$params = $controller->getOutputParams();

		print_r($params);
	}
}
