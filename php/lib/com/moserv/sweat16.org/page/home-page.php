<?php

require_once('com/moserv/sweat16/page/page.php');
require_once('com/moserv/sweat16/loader/product-list-loader.php');

class HomePage extends Page {

	protected function doLoad() {
		$record = parent::doLoad();

		$loader = new ProductListLoader();

		
		$record = array_merge(
			$record,
			array('product-list' => $loader->execute())
		);

		return $record;
	}
}
