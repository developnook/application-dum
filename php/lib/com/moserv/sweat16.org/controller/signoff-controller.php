<?php

require_once('com/moserv/sweat16/controller/controller.php');


class SignoffController extends Controller {


	public function execute() {

		$session = $this->getSession();
		$session->removeVar('user-id');
		$session->removeVar('name');
		$session->removeVar('user-email');

		return true;
	}
}

