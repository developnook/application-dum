<?php

require_once('com/moserv/sweat16/controller/controller.php');


class CartDeleteController extends Controller {

	protected function deleteCart() {
		$params = $this->getInputParams();
		$session = $this->getSession();
		$connection = $session->getConnection();
		$query = $connection->createQuery(
<<<sql
			delete from
				sweat16.cart_product
			where cart_product_id = ?
sql
		);

		$query->setString(1, $params['cart-product-id']);

		$query->open();



		switch ($query->getAffectedRows()) {
			case 0:
				$record = array('code' => 0, 'desc' => 'no record found');
			break;

			case 1:
				$record = array('code' => 1, 'desc' => 'success');
			break;

			default:
				$record = array('code' => -1, 'desc' => 'unknown error');
				
		}

		return $record;
	}

	public function execute() {

		$record = $this->deleteCart();

		$this->setOutputParams($record);
	}
}

