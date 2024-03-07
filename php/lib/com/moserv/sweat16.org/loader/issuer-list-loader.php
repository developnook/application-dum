<?php

require_once('com/moserv/sweat16/loader/loader.php');


class IssuerListLoader extends Loader {

	protected function doExecute($params = null) {

		$json = file_get_contents('https://tep.paysolutions.asia/api/v1/bank/v1/th');
		$list = json_decode($json, true);

		return array('rows' => $list['data']);
	}
}
