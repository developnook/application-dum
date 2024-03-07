<?php

require_once('com/moserv/karbon/session.php');


class Basis {

	public static $__paths = null;

	protected $session;

	public function __construct() {
		$this->session = Session::newInstance();

		if (Basis::$__paths == null) {
			Basis::$__paths = $this->getPaths();
		}
	}

	protected function initialize() { }

	protected function getPaths() {
		global $_SERVER;

		$htdocs = realpath($_SERVER['DOCUMENT_ROOT']);
		$conf = dirname($htdocs).'/conf';
		$class = dirname($htdocs).'/class';
		$content = dirname($htdocs).'content';

		return array(
			'htdocs'	=> $htdocs,
			'conf'		=> $conf,
			'class'		=> $class,
			'content'	=> $content
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
