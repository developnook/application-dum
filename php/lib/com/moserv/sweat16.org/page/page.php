<?php

require_once('com/moserv/sweat16/loader/loader.php');
require_once('com/moserv/sweat16/loader/cart-loader.php');


class Page extends Loader {

	public static $instance;

	protected $capsule;


	public function __construct() {
		parent::__construct();

		Page::$instance = $this;
	}

	public function begin() {
		$paths = $this->getPaths();
		
		include_once("{$paths['conf']}/header.inc.php");
	}

	public function end() {
		$paths = $this->getPaths();

		include_once("{$paths['conf']}/footer.inc.php");
	}

	protected function doLoad() {
		global $_REQUEST;
		$session = $this->getSession();
		$capsule = array();

		if ($session->isSignedOn()) {
			$loader = CartLoader::newInstance();
			$record = $loader->execute($_REQUEST);

			$capsule['cart'] = $record;
		}

		return $capsule;
	}

	public function load() {
		$this->capsule = $this->doLoad();

		return $this->capsule;
	}

	public function getCapsule() {
		return $this->capsule;
	}

	public function getCartCount() {
	}
}
