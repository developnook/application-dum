<?php

require_once('com/moserv/sweat16/controller/controller.php');
require_once('com/moserv/sweat16/loader/cart-loader.php');
require_once('com/moserv/sweat16/loader/province-list-loader.php');



class CheckoutController extends Controller {


	protected function updateQuantity($record) {
		$session = $this->getSession();
		$connection = $session->getConnection();
		$query = $connection->createQuery(
<<<sql
			update sweat16.cart_product
				set quantity = ?
			where cart_product_id = ?
sql
		);

		$query->setInt(1, $record['quantity']);
		$query->setInt(2, $record['cart-product-id']);
		$query->open();
	}


	protected function updateCart($params) {
		foreach (array_keys($params) as $key) {
			if (preg_match('/^item-([0-9]+)$/', $key, $group)) {
				$this->updateQuantity(
					array(
						'cart-product-id'	=> $group[1],
						'quantity'		=> $params[$key]
					)
				);
			}
		}

	}


	public function execute() {
		$params = $this->getInputParams();

		$this->updateCart($params);


		$session = $this->getSession();
		$loader = new CartLoader();

		$cart = $loader->execute();

		$session->setVar('cart', $cart);


##		$province_loader = new ProvinceLoader();
##		$province = $province_loader->execute();
##		$session->setVar('province', $province);

#		$params = $this->getInputParams();


#		$this->setOutputParams($record);
	}
}

