<?php

require_once('com/moserv/net/url.php');
require_once('com/moserv/karbon/controller.php');

class Servlet extends Controller {


	protected function doLog($line) {

		return;

		$timestamp = (new DateTime())->format('Y-m-d H:i:s');

		file_put_contents('/tmp/servlet.log', "{$timestamp} - {$line}\n", FILE_APPEND);
	}

	protected function doBeforeExecute() {
		global $_SERVER;
		global $_REQUEST;


		$this->doLog("REQUEST_METHOD => {$_SERVER['REQUEST_METHOD']}");
		$this->doLog("CONTENT_LENGTH => {$_SERVER['CONTENT_LENGTH']}");


		if (array_key_exists('REQUEST_METHOD', $_SERVER) && strtoupper($_SERVER['REQUEST_METHOD']) === 'POST') {

			$data = file_get_contents('php://input');

			$this->doLog("POST DATA => {$data}");
			$this->doLog("CONTENT TYPE => {$_SERVER['CONTENT_TYPE']}");

			if (array_key_exists('CONTENT_TYPE', $_SERVER)) {
				if (preg_match('/^application\/json/i', $_SERVER['CONTENT_TYPE'])) {
					$_REQUEST = json_decode($data, true);
				}
#				elseif (preg_match('/^multipart\/form-data/i', $_SERVER['CONTENT_TYPE'])) {
#					$length = (int)$_SERVER['CONTENT_LENGTH'];
#
#					$ndata = fread(STDIN, $length);
#					$this->doLog("MULTIPART DATA => {$ndata}");
#					$this->doLog("MULTIPART DATA => {$HTTP_RAW_POST_DATA}");
#				}
			}
		}


#		if (
#			array_key_exists('REQUEST_METHOD', $_SERVER)			&&
#			$_SERVER['REQUEST_METHOD'] === 'POST'				&&
#			array_key_exists('CONTENT_TYPE', $_SERVER)			&&
#			preg_match('/^application\/json/i', $_SERVER['CONTENT_TYPE'])
#
#		) {
#			$json = file_get_contents('php://input');
#			$_REQUEST = json_decode($json, true);
#		}

		
	}

	protected function doExecute() {
		return array();
	}
	
	protected function doAfterExecute(&$params) {
		global $_SERVER;

#		if (array_key_exists('HTTP_AUTHORIZATION', $_SERVER)) {
		if ($this->session->isApp() && $this->session->getRequestedWith() == 'apicall') {
			header('Content-Type: application/json; charset=utf-8');

			echo json_encode($params, JSON_UNESCAPED_UNICODE);

			exit;
		}
	}

	public function execute() {
		$this->doBeforeExecute();

		$params = $this->doExecute();

		$this->doAfterExecute($params);

		return $params;	
	}

/*
	protected function redirect($path) {
		$url = new Url();

		$url->setPath($path);
		$url->redirect();
	}
*/
}


