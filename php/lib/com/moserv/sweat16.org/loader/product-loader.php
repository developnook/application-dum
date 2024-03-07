<?php

require_once('com/moserv/sweat16/loader/loader.php');


class ProductLoader extends Loader {

	protected function doExecute($params = null) {
		


		$capsule = $this->loadProduct($params);



		if ($capsule == null) {
			return null;
		}

		$capsule['img-links'] = $this->loadImages($params);

		return $capsule;
	}


	protected function loadProduct($params) {
		$session = $this->getSession();
		$connection = $session->getConnection();

		$query = $connection->createQuery(
<<<sql
			select
				product_id	as 'product-id',
				product_title	as 'title',
				product_price	as 'product-price',
				product_desc	as 'desc'
			from sweat16.product
			where product_id = ?
			and enabled = 1
sql
		);

		$query->setInt(1, $params['product-id']);
		$query->open();

		$rows = $query->getResultArray();

		return (count($rows) == 0)? null: $rows[0];
	}

	protected function loadImages($params) {
		$session = $this->getSession();
		$connection = $session->getConnection();

		$query = $connection->createQuery(
<<<sql
			select
				product_id as 'product-id',
				rank
			from sweat16.product_image
			where product_id = ?
			and enabled = 1
			order by rank
sql
		);

		$query->setInt(1, $params['product-id']);
		$query->open();

		$rows = $query->getResultArray();

		$images = array();

		foreach ($rows as $row) {
			$images[] = sprintf('/image/product/p%05d-%02d.jpg', $row['product-id'], $row['rank']);
		}

		return $images;
	}
}
