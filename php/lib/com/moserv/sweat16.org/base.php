<?php

require_once('com/moserv/sweat16/session.php');


class Base {

	protected $session;

	public function __construct() {
		$this->session = Session::newInstance();
	}

	protected function getPaths() {
		global $_SERVER;

		$htdocs = realpath($_SERVER['DOCUMENT_ROOT']);
		$conf = dirname($htdocs).'/conf';

		return array(
			'htdocs'=> $htdocs,
			'conf'	=> $conf
		);
	}

	public function getSession() {
		return $this->session;
	}

	protected function redirect($path, $urlparam = false) {
		$rurl = new Url();

		$rurl->setPath($path);

		if ($urlparam) {
			$curl = new Url();
			$rurl->setParam('url', $curl->toString());
		}

		$rurl->redirect();
	}
}
