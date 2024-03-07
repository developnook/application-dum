<?php

require_once('com/moserv/karbon/loader.php');
require_once('com/moserv/net/url.php');
#require_once('com/moserv/karbon/shop/loader/cart-loader.php');


class Page extends Loader {

	const hdr_html		= 0x00;
	const hdr_head		= 0x01;
	const hdr_body		= 0x02;
	const hdr_wrapper	= 0x03;
	const hdr_content	= 0x04;

	public static $headers = array(
		Page::hdr_html		=> 'header-00-html.inc.php',
		Page::hdr_head		=> 'header-01-head.inc.php',
		Page::hdr_body		=> 'header-02-body.inc.php',
		Page::hdr_wrapper	=> 'header-03-wrapper.inc.php',
		Page::hdr_content	=> 'header-04-content.inc.php'
	);

	public static $instance;

	protected $capsule;
	protected $title;
	protected $extTop;
	protected $extBottom;
	protected $paths;


	public function __construct() {
		parent::__construct();

		Page::$instance = $this;
		$this->paths = Basis::$__paths;

		$this->title = '';
		$this->extTop = 0;
		$this->extBottom = 0;
	}


	public function begin($hdr = PHP_INT_MAX) {
//		$headers = Page::$headers;
//		$layout = ($this->session->isMobile())? 'mobile': 'desktop';
//
//		for ($ind = 0; $ind < count($headers); $ind++) {
//			if ($ind <= $hdr) {
//				$header = "{$this->paths['conf']}/header/{$layout}/{$headers[$ind]}";
//				include_once($header);
//			}
//			else
//				break;
//		}

		$header = "{$this->paths['conf']}/header.inc.php";
		include_once($header);
	}


	public function end($ftr = PHP_INT_MAX) {
//		$layout = ($this->session->isMobile())? 'mobile': 'desktop';
//		$footer = "{$this->paths['conf']}/footer/{$layout}/footer.inc.php";
//		include_once($footer);
		$footer = "{$this->paths['conf']}/footer.inc.php";
		include_once($footer);
	}

	protected function doLoad() {
		return array();
	}

	public function load() {
		$this->doBeforeLoad();

		$this->capsule = $this->doLoad();

		$this->doAfterLoad();



		return $this->capsule;
	}


	protected function doBeforeLoad() {
		global $_SERVER;
		global $_REQUEST;


		if (!$this->session->isApp() && !$this->session->isMobile() && $this->isMobileRequired()) {
			echo 'Sorry! this site is for mobile only...';
			exit;
		}


		if (
			array_key_exists('REQUEST_METHOD', $_SERVER)			&&
			$_SERVER['REQUEST_METHOD'] === 'POST'				&&
			array_key_exists('CONTENT_TYPE', $_SERVER)			&&
			preg_match('/^application\/json/i', $_SERVER['CONTENT_TYPE'])

		) {
			$json = file_get_contents('php://input');
			$_REQUEST = json_decode($json, true);
		}


		if ($this->isLoginRequired() && !$this->isLoggedIn()) {

			if ($this->session->isApp()) {
				header("HTTP/1.1 401 Unauthorized");
			}
			else {
				$currentUrl = new Url();

				$loginUrl = new Url();
				$loginUrl->setParams(null);

				$loginUrl->setPath('/authen/signon/');
				$loginUrl->setParam('__url', $currentUrl->toString());

				$loginUrl->redirect();
			}

			exit;
		}
	}

	protected function doAfterLoad() {

		if($this->session->isApp() && $this->session->getRequestedWith() == 'apicall') {

			header('Content-Type: application/json; charset=utf-8');
			echo json_encode($this->capsule, JSON_UNESCAPED_UNICODE);

			exit;
		}
	}

	public function getCapsule() {
		return $this->capsule;
	}

	public function getCartCount() {
	}

	public function getTitle() {
		return $this->title;
	}

	public function setTitle($title) {
		$this->title = $title;
	}

	public function setExtTop($extTop) {
		$this->extTop = $extTop;
	}

	public function getExtTop() {
		return $this->extTop;
	}

	public function getExtBottom() {
		return $this->extBottom;
	}



	protected function doHeaderDisplay() {
		return true;
	}

	protected function doFooterDisplay() {
		return true;
	}

	final public function isHeaderDisplay() {
		if ($this->session->isApp() && $this->session->getRequestedWith() == 'webview')
			return false;
		else
			return $this->doHeaderDisplay();
	}

	final public function isFooterDisplay() {
		if ($this->session->isApp() && $this->session->getRequestedWith() == 'webview')
			return false;
		else
			return $this->doFooterDisplay();
	}

	public function getButtonSlots() {
		return array(
			'menu'		=> array('name' => 'menu',	'display' => false),
			'back'		=> array('name' => 'back',	'display' => true),
			'cart'		=> array('name' => 'cart',	'display' => true),
			'member'	=> array('name' => 'member',	'display' => true)
		);
	}

	public function getBackUrl() {
		global $_REQUEST;
		$url = null;

		if (empty($_REQUEST['__url'])) {
			$url = new Url();
			$url->setParams(null);
			$url->setPath('/home/');
		}
		else {
			$url = new Url($_REQUEST['__url']);
		}

		return $url;
	}

	public function isLoggedIn() {
		return $this->session->isSignedOn();
	}

	public function isLoginRequired() {
		return false;
	}

	public function isMobileRequired() {
		return false;
	}

	public function dispatch() {
		$cwd = getcwd();

		if ($this->session->isMobile())
			include_once("{$cwd}/index.mobile.php");
		else
			include_once("{$cwd}/index.desktop.php");
	}
}
