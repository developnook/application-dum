<?php

require_once('com/moserv/sweat16/loader/loader.php');


class ProductListLoader extends Loader {

	protected function doExecute($params = null) {
		$session = $this->getSession();
		$connection = $session->getConnection();

		$query = $connection->createQuery(
<<<sql
			select
				product_id	as 'product-id',
				product_name	as 'product-name',
				product_title	as 'product-title',
				product_price	as 'product-price'
			from sweat16.product
			where enabled = 1
			order by product_id
sql
		);

		$query->open();

		$rows = $query->getResultArray();

		
		return $rows;
	}
}
