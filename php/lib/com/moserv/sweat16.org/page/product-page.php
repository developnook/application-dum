<?php

require_once('com/moserv/sweat16/page/page.php');
require_once('com/moserv/sweat16/loader/product-loader.php');

class ProductPage extends Page {

	protected function doLoad() {
		$record = parent::doLoad();
		global $_REQUEST;

		$loader = new ProductLoader();


		$record = array_merge(
			$record,
			array('product' => $loader->execute($_REQUEST))
		);

		return $record;
	}
}
