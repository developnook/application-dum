<?php

require_once('com/moserv/karbon/base.php');

class Page extends Base {
	protected function doLoad() {
		return null;
	}

	public function load() {
		return $this->doLoad();
	}
}
