<?php

require_once('com/moserv/sweat16/loader/loader.php');


class CartLoader extends Loader {

	public static $instance = null;

	private $capsule;


	public static function newInstance() {
		if (CartLoader::$instance == null) {
			CartLoader::$instance = new CartLoader();
		}

		return CartLoader::$instance;
	}

	protected function doExecute($params = null) {
		$session = $this->getSession();
		$connection = $session->getConnection();

		$query = $connection->createQuery(
<<<sql
			select
				p.product_id			as 'product-id',
				p.product_title			as 'title',
				p.product_price			as 'unit-price',
				p.product_price * cp.quantity	as 'quantity-price',
				cp.cart_product_id		as 'cart-product-id',
				cp.quantity			as 'quantity'
			from sweat16.cart c
				join sweat16.cart_product cp using (cart_id)
				join sweat16.product p using (product_id)
			where c.user_id = ?
			and c.checked_out = 0
			order by p.product_title
sql
		);

		$query->setInt(1, $session->getVar('user-id'));

		$query->open();

		$rows = $query->getResultArray();
		$totalPrice = 0;
		$totalQuantity = 0;

		foreach ($rows as &$row) {
			$totalPrice += $row['quantity-price'];
			$totalQuantity += $row['quantity'];
			$row['image'] = sprintf('/image/product/p%05d-00.jpg', $row['product-id']);
		}

		$capsule = array(
			'total-price'		=> $totalPrice,
			'rows'			=> $rows,
			'total-quantity'	=> $totalQuantity
		);

		return $capsule;
	}
}
