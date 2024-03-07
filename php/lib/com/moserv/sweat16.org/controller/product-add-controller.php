<?php

require_once('com/moserv/sweat16/controller/controller.php');


class ProductAddController extends Controller {

	protected function queryCart() {
		$params = $this->getInputParams();

		$session = $this->getSession();
		$connection = $session->getConnection();
		$query = $connection->createQuery(
<<<sql
			select
				cart_id
			from sweat16.cart
			where user_id = ?
			and checked_out = 0
sql
		);

		$query->setInt(1, $session->getVar('user-id'));
		$query->open();

		$rows = $query->getResultArray();

		$record = (count($rows) == 0)? null: array(
			'cart-id'    => $rows[0]['cart_id'],
			'product-id' => $params['product-id']
		);

		return $record;
	}

	protected function newCart() {
		$params = $this->getInputParams();

		$session = $this->getSession();
		$connection = $session->getConnection();
		$query = $connection->createQuery(
<<<sql
			insert into sweat16.cart (
				user_id
			)
			values (
				?
			)
sql
		);

		$query->setInt(1, $session->getVar('user-id'));
		$query->open();

		$record = array(
			'cart-id'    => $connection->lastId(),
			'product-id' => $params['product-id']
		);

		return $record;
	}

	protected function queryCartProduct($record) {
		$params = $this->getInputParams();
		$session = $this->getSession();
		$connection = $session->getConnection();
		$query = $connection->createQuery(
<<<sql
			select
				cart_product_id
			from sweat16.cart_product
			where cart_id = ?
			and product_id = ?
sql
		);

		$query->setInt(1, $record['cart-id']);
		$query->setInt(2, $params['product-id']);

		$query->open();

		$rows = $query->getResultArray();

		return (count($rows) == 0)? null: array(
			'cart-id'	  => $record['cart-id'],
			'product-id'	  => $params['product-id'],
			'cart-product-id' => $rows[0]['cart_product_id']
		);
	}

	protected function newCartProduct($record) {

		$params = $this->getInputParams();
		$session = $this->getSession();
		$connection = $session->getConnection();
		$query = $connection->createQuery(
<<<sql
			insert into sweat16.cart_product (
				cart_id,
				product_id,
				quantity
			)
			values (
				?,
				?,
				?
			)
sql
		);

		$query->setInt(1, $record['cart-id']);
		$query->setInt(2, $params['product-id']);
		$query->setInt(3, 0);

		$query->open();

		return array(
			'cart-id'         => $record['cart-id'],
			'product-id'	  => $params['product-id'],
			'cart-product-id' => $connection->lastId()
		);
	}

	protected function updateCartProduct($record) {
		$params = $this->getInputParams();

		$session = $this->getSession();
		$connection = $session->getConnection();
		$query = $connection->createQuery(
<<<sql
			update sweat16.cart_product
				set quantity = quantity + ?
			where cart_product_id = ?
sql
		);

		$query->setInt(1, $params['quantity']);
		$query->setInt(2, $record['cart-product-id']);

		$query->open();

		return array(
			'cart-id'	  => $record['cart-id'],
			'product-id'	  => $params['product-id'],
			'cart-product-id' => $record['cart-product-id']
		);
	}

	public function execute() {
		$session = $this->getSession();

		if (!$session->isSignedOn()) {
			$this->setOutputParams(null);
			return;
		}

		if (($cart = $this->queryCart()) == null) {
			$cart = $this->newCart();
		}

		if (($cartProduct = $this->queryCartProduct($cart)) == null) {
			$cartProduct = $this->newCartProduct($cart);
		}

		$cartProduct = $this->updateCartProduct($cartProduct);


		$this->setOutputParams($cartProduct);
	}
}

