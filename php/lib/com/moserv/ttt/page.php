<?php

require_once('com/moserv/ttt/config.php');
require_once('com/moserv/util/web.php');

class Page {

	public static $page;

	protected $title;
	protected $javascripts;
	protected $stylesheets;
	protected $loginPage;
	protected $contentType;
	protected $onloadfunc;

	public function __construct($title = 'page') {
		$this->title = $title;

		$this->javascripts = array();
		$this->stylesheets = array();

		$this->loginPage = Web::curPageUrl() . '/main/login.php';
		$this->contentType = 'application/xhtml+xml; charset=utf-8';

		$this->onloadfunc = "";

		$this->addCss('/css/main.css');
		$this->addCss('/css/panel.css');

		self::$page = $this;
	}

	public function getTitle() {
		return $this->title;
	}

	public function addJs($javascript) {
		if (array_search($javascript, $this->javascripts) === FALSE)
			$this->javascripts[] = $javascript;
	}

	public function addCss($stylesheet) {
		if (array_search($stylesheet, $this->stylesheets) === FALSE)
		$this->stylesheets[] = $stylesheet;
	}

	public function begin() {
		global $_SESSION;

		session_start();

		if (empty($_SESSION['user_id'])) {

			$url = Web::curPageUrl();
			$urlParam = urlencode($url);

			header("location: {$this->loginPage}?url=$urlParam");

			exit;
		}

		header("content-type: {$this->contentType}");
		include('com/moserv/ttt/html/begin.php');
	}

	public function end() {
		include('com/moserv/ttt/html/end.php');
	}

	public function includeStylesheets() {
		foreach ($this->stylesheets as $stylesheet) {
			Web::includeCss($stylesheet);
		}
	}

	public function includeJavascripts() {
		foreach ($this->javascripts as $javascript) {
			Web::includeJs($javascript);
		}
	}

	public function setOnloadfunc($onloadfunc) {
		$this->onloadfunc = $onloadfunc;
	}

	public function getOnloadfunc() {
		return $this->onloadfunc;
	}
}

?>
