<?php

require_once('com/moserv/sweat16/controller/controller.php');
#require_once('com/moserv/sweat16/db/purchase-order.php');
#require_once('com/moserv/sweat16/db/user-address.php');
require_once('com/moserv/sweat16/loader/purchase-order-loader.php');


class PlaceOrderController extends Controller {

	protected function queryUserAddress($record) {

		$session = $this->getSession();
		$connection = $session->getConnection();

		$query = $connection->createQuery(
<<<sql
			select
				user_address_id
			from sweat16.user_address
			where user_id = ?
			and name = ?
			and sur_name = ?
			and phone = ?
			and subdistrict_id = ?
			and zip_code = ?
sql
		);

		$query->setInt   (1, $record['user-id']);
		$query->setString(2, $record['name']);
		$query->setString(3, $record['sur-name']);
		$query->setString(4, $record['phone']);
		$query->setInt   (5, $record['sub-district-id']);
		$query->setString(6, $record['zip-code']);


		$query->open();

		$rows = $query->getResultArray();

		return (count($rows) == 0)? 0: $rows[0]['user_address_id'];
	}

	protected function newUserAddress($record) {
		$session = $this->getSession();
		$connection = $session->getConnection();

		$query = $connection->createQuery(
<<<sql
			insert into sweat16.user_address (
				user_id,
				name,
				sur_name,
				phone,
				address,
				subdistrict_id,
				zip_code
			)
			values (?, ?, ?, ?, ?, ?, ?)
sql
		);

		$query->setInt   (1, $record['user-id']);
		$query->setString(2, $record['name']);
		$query->setString(3, $record['sur-name']);
		$query->setString(4, $record['phone']);
		$query->setString(5, $record['address']);
		$query->setInt   (6, $record['sub-district-id']);
		$query->setString(7, $record['zip-code']);
		
		$query->open();

		return $connection->lastId();
	}

	protected function getUserAddress($record) {

		if (($userAddressId = $this->queryUserAddress($record)) == 0) {
			$userAddressId = $this->newUserAddress($record);
		}



		return $userAddressId;
	}

	protected function newPurchaseOrder($record) {
		$session = $this->getSession();
		$connection = $session->getConnection();

		$query = $connection->createQuery(
<<<sql
			insert into sweat16.purchase_order (
				cart_id,
				cart_price,
				shipping_id,
				shipping_email,
				shipping_price,
				shipping_address_id,
				shipping_address_line,
				billing_address_id,
				payment_type_id,
				save_address
			)
			values (
				?,
				?,
				?,
				?,
				?,
				?,
				?,
				?,
				?,
				?
			)
sql
		);

		$query->setInt   (1,  $record['cart-id']);
		$query->setFloat (2,  $record['cart-price']);
		$query->setInt   (3,  $record['shipping-id']);
		$query->setString(4,  $record['shipping-email']);
		$query->setFloat (5,  $record['shipping-price']);
		$query->setInt   (6,  $record['shipping-address-id']);
		$query->setString(7,  $record['shipping-address-line']);
		$query->setInt   (8,  $record['billing-address-id']);
		$query->setInt   (9,  $record['payment-type-id']);
		$query->setInt   (10, $record['save-address']);

		$query->open();

		return $connection->lastId();
	}



	protected function queryCart($record) {
		$session = $this->getSession();
		$connection = $session->getConnection();

		$query = $connection->createQuery(
#<<<sql
#			select
#				c.cart_id
#			from sweat16.cart c
#				left join purchase_order po using (cart_id)
#			where c.user_id = ?
#			and c.checked_out = 0
#			and po.purchase_order_id is null
#sql
#<<<sql
#			select
#				c.cart_id,
#				count(cp.cart_product_id)	as item_count,
#				sum(cp.quantity)		as total_count,
#				sum(p.price * cp.quantity)	as total_price
#			from sweat16.cart c
#				left join purchase_order po using (cart_id)
#				left join cart_product cp using (cart_id)
#				left join product p using (product_id)
#			where c.user_id = ?
#			and c.checked_out = 0
#			and po.purchase_order_id is null
#			group by c.cart_id
#sql

<<<sql
			select
				c.cart_id,
				count(cp.cart_product_id)	as item_count,
				sum(cp.quantity)		as total_count,
				sum(p.product_price * cp.quantity)	as total_price
			from sweat16.cart c
				left join purchase_order po on po.cart_id = c.cart_id and po.status = 1
				left join cart_product cp on cp.cart_id = c.cart_id
				left join product p using (product_id)
			where c.user_id = ?
			and c.checked_out = 0
			and po.purchase_order_id is null
			group by c.cart_id
sql
		);

		$query->setInt(1, $record['user-id']);

		$query->open();

		$rows = $query->getResultArray();

#		return (count($rows) == 0)? 0: $rows[0]['cart_id'];
		return (count($rows) == 0)? array(0, 0, 0, 0): array(
			$rows[0]['cart_id'],
			$rows[0]['item_count'],
			$rows[0]['total_count'],
			$rows[0]['total_price']
		);
	}

	protected function updateCart($record) {
		$session = $this->getSession();
		$connection = $session->getConnection();

		$query = $connection->createQuery(
<<<sql
			update sweat16.cart
			set checked_out = 1
			where cart_id = ?
sql
		);

		$query->setInt(1, $record['cart-id']);

		$query->open();

		return 1;
	}

	protected function updatePoCode($record) {
		$session = $this->getSession();
		$connection = $session->getConnection();

		$query = $connection->createQuery(
<<<sql
			update sweat16.purchase_order
			set po_code =
				concat('SWT', reverse(lpad(purchase_order_id, 5, '0')), date_format(sys_timestamp, '%f'))
			where purchase_order_id = ?
sql
		);

		$query->setInt(1, $record['purchase-order-id']);

		$query->open();

		return 1;
	}

	protected function queryPoCode($record) {
		$session = $this->getSession();
		$connection = $session->getConnection();

		$query = $connection->createQuery(
<<<sql
			select
				sys_timestamp,
				po_code
			from sweat16.purchase_order
			where purchase_order_id = ?
sql
		);

		$query->setInt(1, $record['purchase-order-id']);

		$query->open();

		$rows = $query->getResultArray();

		return (count($rows) == 0)? array(): array(
			$rows[0]['sys_timestamp'],
			$rows[0]['po_code']
		);
	}

	protected function newPaymentByCard($record) {
		$session = $this->getSession();
		$connection = $session->getConnection();

		$query = $connection->createQuery(
<<<sql
			insert into sweat16.payment_by_card (
				card_no,
				name_on_card,
				expire,
				cvv,
				purchase_order_id
			)
			values (?, ?, ?, ?, ?)
sql
		);

		$query->setString(1, $record['card-no']);
		$query->setString(2, $record['name-on-card']);
		$query->setString(3, $record['expire']);
		$query->setString(4, $record['cvv']);
		$query->setInt   (5, $record['purchase-order-id']);

		$query->open();

		return $connection->lastId();
	}

	protected function sendMail($record) {

		$loader = new PurchaseOrderLoader();

		$capsule = $loader->execute(array('purchase-order-id' => $record['purchase-order-id']));

		$session = $this->getSession();
		$mailer = $session->getMailer();

		$paths = $this->getPaths();

		global $globalvar;

		$globalvar = $capsule;
		
		ob_start();
		include("{$paths['conf']}/mail-purchase-order.inc.php");
		$body = ob_get_clean();

		$mailer->execute(
			array(
				'type'		=> 4,
				'to'		=> $capsule['email'],
				'subject'	=> "ยืนยันใบสั่งซื้อ {$capsule['purchase-order-code']}",
				'body'		=> $body
			)
		);

	}

	public function execute() {
		global $_SESSION;

		$session = $this->getSession();
		$params = $this->getInputParams();

#		print_r($params);
#		exit;

		$record = array();

		list(
			$record['cart-id'],
			$record['cart-item-count'],
			$record['cart-total-count'],
			$record['cart-total-price']
		) = $this->queryCart(array('user-id' => $_SESSION['user-id']));

		if ($record['cart-id'] != 0) {

			$record['shipping-address-id'] = $this->getUserAddress(
				array(
					'name'			=> $_SESSION['customer-information']['name'],
					'sur-name'		=> $_SESSION['customer-information']['sur-name'],
					'phone'			=> $_SESSION['customer-information']['phone'],
					'address'		=> $_SESSION['customer-information']['address'],
					'sub-district-id'	=> $_SESSION['customer-information']['sub-district-id'],
					'zip-code'		=> $_SESSION['customer-information']['zip-code'],
					'user-id'		=> $_SESSION['user-id']
				)
			);


			if ($params['billing-address-use'] == 1) { 
				$record['billing-address-id'] = $record['shipping-address-id'];
			}
			else {
				//$record['shipping-address-id'] = $this->getUserAddress(
				$record['billing-address-id'] = $this->getUserAddress(
					array(
						'name'			=> $params['billing-name'],
						'sur-name'		=> $params['billing-sur-name'],
						'phone'			=> $params['billing-phone'],
						'address'		=> $params['billing-address'],
						'sub-district-id'	=> $params['billing-sub-district-id'],
						'zip-code'		=> $params['billing-zip-code'],
						'user-id'		=> $_SESSION['user-id']
					)
				);
			}


			$record['purchase-order-id'] = $this->newPurchaseOrder(
				array(
					'cart-price'		=> $record['cart-total-price'],
					'shipping-id'		=> $params['shipping-id'],
					'shipping-email'	=> $_SESSION['customer-information']['shipping-email'],
					'shipping-price'	=> $_SESSION['customer-information']['shipping-price'],
					'shipping-address-id'	=> $record['shipping-address-id'],
					'shipping-address-line'	=> $_SESSION['customer-information']['shipping-address'],
					'billing-address-id'	=> $record['billing-address-id'],
					'payment-type-id'	=> $params['payment-type-id'],
					'cart-id'		=> $record['cart-id'],
					'save-address'		=> $_SESSION['customer-information']['save-address']
				)
			);


			$this->updatePoCode(array('purchase-order-id' => $record['purchase-order-id']));

			list($record['sys-timestamp'], $record['po-code']) = $this->queryPoCode(
				array('purchase-order-id' => $record['purchase-order-id'])
			);


			if ($params['payment-type-id'] == 2) {
				$record['payment-by-card-id'] = $this->newPaymentByCard(
					array(
						'purchase-order-id'	=> $record['purchase-order-id'],
						'card-no'		=> $params['payment-card-no'],
						'name-on-card'		=> $params['payment-card-name-on-card'],
						'expire'		=> $params['payment-card-exp'],
						'cvv'			=> $params['payment-card-cvv']
					)
				);
			}


#			unset($_SESSION['cart']);
#			unset($_SESSION['customer-information']);
#			$_SESSION['last-purchase-order-id'] = $record['purchase-order-id'];

#			print_r($params);
#			exit;

			if ($params['payment-type-id'] == 1) {
				$this->updateCart(array('cart-id' => $record['cart-id']));
			}


			$this->sendMail(
				array(
					'payment-type-id'	=> $params['payment-type-id'],
					'purchase-order-id'	=> $record['purchase-order-id']
				)
			);
		}

		$this->setOutputParams($record);
	}
}

