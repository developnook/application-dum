<?php

class PurchaseOrder {

	private $shippingAddressId;
	private $billingAddressId;
	private $shippingAddressLine;

	private $shippingPrice;
	private $cartId;

	public function create() {


		$connecton = $this->session->getConnection();

		$query = $connection->createQuery(
<<<sql
			insert into sweat16.purchase_order (
				cart_id,
				shipping_price,
				shipping_address_id,
				shipping_address_line,
				billing_address_id
			)
			values (
				?,
				?,
				?,
				?
			)
sql
		);

		$query->setInt(1, $this->cartId);
		$query->setFloat(2, $this->shippingPrice);
		$query->setInt(3, $this->shippingAddressId);
		$query->setString(4, $this->shippingAddressLine);
		$query->setInt(5, $this->billingAddressId);


		$query->open();

		return $connection->lastId();
	}

	public function setShippingAddressId($shippingAddressId) {
		$this->shippingAddressId = $shippingAddressId;
	}

	public function setBillingAddressId($billingAddressId) {
		$this->billingAddressId = $billingAddressId;
	}

	public function setShippingAddressLine($shippingAddressLine) {
		$this->shippingAddressLine = $shippingAddressLine;
	}

	public function setShippingPrice($shippingPrice) {
		$this->shippingPrice = $shippingPrice;
	}

	public function setCartId($cartId) {
		$this->cartId = $cartId;
	}
}

