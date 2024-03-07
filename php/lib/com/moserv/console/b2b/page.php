<?php

require_once('com/moserv/sql/connection.php');
require_once('com/moserv/net/session.php');
require_once('com/moserv/util/web.php');

class Page {

	const pg_signin		= '/app/signin';
	const js_signin		= '/json/signin.json.php';
	const pg_landing	= '/app/browser';

	public static $page	= null;

	private $session;
	private $title;
	private $ext;

	public function __construct() {
		$this->session = WapSession::create();
		$this->title = 'Untitled';
		$this->ext = null;
		Page::$page = $this;
	}

	public function setTitle($title) {
		$this->title = $title;
	}

	public function getTitle() {
		return $this->title;
	}

	public function setExt($ext) {
		$this->ext = strtolower($ext);
	}

	public function redirect($path) {
		$url = Web::curPageUrl(false) . $path;

		header("location: $url");
		exit;
	}

	protected function isSigninPage() {
		global $_SERVER;

		return (strpos($_SERVER['REQUEST_URI'], Page::pg_signin) === 0 || strpos($_SERVER['REQUEST_URI'], Page::js_signin) === 0);
	}

	protected function isLandingPage() {
		global $_SERVER;

		return (strpos($_SERVER['REQUEST_URI'], Page::pg_landing) === 0);
	}

	protected function foundUserId() {
		Logger::$logger->info("b2b: filename = " . $this->session->getVar('userId'));
		return ($this->session->getVar('userId') != null);
	}

	public function begin() {
		global $_SERVER;

		$this->session->start();

		Logger::$logger->info("b2b: filename => {$_SERVER['REQUEST_URI']}");

		if (!$this->isSigninPage() && !$this->foundUserId()) {
			Logger::$logger->info("b2b: filename = quit 1");
			$this->redirect(Page::pg_signin.'?url='.urlencode(Web::curPageUrl()));

			exit;
		}

		if ($this->isSigninPage() && $this->foundUserId()) {
			Logger::$logger->info("b2b: filename = quit 2");
			$this->redirect(Page::pg_landing);

			exit;
		}

		if ($this->ext != null) {
			$filename = "1.{$this->ext}";
			Logger::$logger->info("filename => $filename");

			$home = dirname(dirname($_SERVER['DOCUMENT_ROOT']));
			$mime = Web::getMimeType($filename, "{$home}/conf/mime.types");
#			$mime = Web::getMimeType($filename, Page::pt_mime);

			if ($mime !== false) {
				header("content-type: $mime");
			}

			if ($this->ext == 'xhtml' && !$this->isSigninPage()) {
				include('com/moserv/console/b2b/page-begin.php');
			}
		}

	}

	public function end() {
		if ($this->ext == 'xhtml' && !$this->isSigninPage())
			include('com/moserv/console/b2b/page-end.php');
	}

	public function getSession() {
		return $this->session;
	}

	public function getPageName() {
		global $_SERVER;

		$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
		$name = basename($path);

		return $name;
	}
}


?>
