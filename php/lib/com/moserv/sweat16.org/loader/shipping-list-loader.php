<?php

require_once('com/moserv/sweat16/loader/loader.php');


class ShippingListLoader extends Loader {

	protected function doExecute($params = null) {
		$session = $this->getSession();
		$connection = $session->getConnection();
/*
		$query = $connection->createQuery(

<<<sql
			select
				s.shipping_id				as 'shipping-id',
				s.shipping_name_th			as 'shipping-name',
				s.shipping_days				as 'shipping-days',
				sum(cp.quantity * p.shipping_price)	as 'shipping-price'
			from sweat16.cart c
				join sweat16.cart_product cp using (cart_id)
				join sweat16.packing p using (product_id)
				join sweat16.shipping s using (shipping_id)
			where c.user_id = ?
			and c.checked_out = 0
			and p.quantity = 1
			and s.enabled = 1
			group by s.shipping_id, s.shipping_name_th, s.shipping_days
			order by s.shipping_id
sql
		);

		$query->setInt(1, $params['user-id']);
		$query->open();

		$rows = $query->getResultArray();
*/

		$query = $connection->createQuery(
<<<sql
			select
				sum(cp.quantity) as quantity
			from sweat16.cart c
				join sweat16.cart_product cp using (cart_id)
			where c.user_id = ?
			and c.checked_out = 0
sql
		);

		$query->setInt(1, $params['user-id']);
		$query->open();
		$rows = $query->getResultArray();
		$quantity = (count($rows) == 0)? 0: $rows[0]['quantity'];

		$packs = array(
			array('min' => 1,  'max' => 2,   'price' => 50),
			array('min' => 3,  'max' => 8,	 'price' => 100),
			array('min' => 9,  'max' => 20,	 'price' => 150),
			array('min' => 21, 'max' => 100, 'price' => 200)
		);

		$price = 0;
		$counter = $quantity;

		while ($counter > 0) {

			$lpack = null;

			foreach ($packs as $pack) {
				if ($counter >= $pack['min'] && $counter <= $pack['max']) {
					$lpack = $pack;
					break;
				}
			}

			if ($lpack == null) {$lpack = $packs[count($packs) - 1]; }

			$counter -= min($counter, $pack['max']);
			$price += $pack['price'];
		}

		
		$rows = array(
#			array('shipping-id' => 1, 'shipping-name' => 'ไปรษณีย์ไทย', 'shipping-days' => 7, 'shipping-price' => 0), #disabled
			array('shipping-id' => 2, 'shipping-name' => 'ขนส่งเอกชน', 'shipping-days' => 3, 'shipping-price' => $price)
		);


		return array( 'rows' => $rows );
	}
}
