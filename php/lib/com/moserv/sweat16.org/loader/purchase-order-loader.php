<?php

require_once('com/moserv/sweat16/loader/loader.php');
require_once('com/moserv/net/url.php');


class PurchaseOrderLoader extends Loader {

	protected function doExecute($params = null) {
		$capsule = $this->loadPurchaseOrder($params);
		$capsule['rows'] = $this->loadItems($params);

		return $capsule;
	}


	protected function loadItems($record) {
		$session = $this->getSession();
		$connection = $session->getConnection();
		$url = new Url();
		$url->setPath('');
		$url->setParams(null);

		$query = $connection->createQuery(
<<<sql
			select
				p.product_title as 'product-title',
				p.product_price as 'product-price',
				cp.quantity,
				concat(?, '/image/product/p', lpad(p.product_id, 5, '0'), '-00.jpg') as 'image'
			from sweat16.purchase_order po
				join sweat16.cart_product cp using (cart_id)
				join sweat16.product p using (product_id)
			where po.purchase_order_id = ?
			order by p.product_title
sql
		);


		$query->setString(1, $url->toString());
		$query->setInt(2, $record['purchase-order-id']);
		$query->open();

		$rows = $query->getResultArray();

		return $rows;
	}

	protected function loadPurchaseOrder($record) {
		$session = $this->getSession();
		$connection = $session->getConnection();

		$query = $connection->createQuery(
<<<sql
			select
				u.email,
				concat(u.name, ' ', u.surname)				as 'user-name',
				date_format(po.sys_timestamp, '%Y-%m-%d %H:%i')		as 'sys-timestamp',
				date_add(date(po.sys_timestamp), interval 2 day)	as 'expire-date',
				po.po_code						as 'purchase-order-code',
				po.payment_type_id					as 'payment-type-id',
				po.shipping_address_line				as 'shipping-address-line',
				sa.phone,
				po.shipping_address_id					as 'shipping-address-id',
				po.billing_address_id					as 'billing-address-id',
				concat(
					ba.name, ' ',
					ba.sur_name, '\n',
					ba.address, '\n',
					sd.subdistrict_name_th, ' ',
					d.district_name_th, ' ',
					p.province_name_th, '\n',
					ba.zip_code
				)							as 'billing-address-line',
				po.cart_price						as 'cart-price',
				po.shipping_price					as 'shipping-price',
				po.cart_price + po.shipping_price			as 'net-price',
				s.shipping_name_th					as 'shipping-name',
				po.status
				

			from sweat16.purchase_order po
				join sweat16.cart c using (cart_id)
				join sweat16.user u using (user_id)
				join sweat16.user_address sa on po.shipping_address_id = sa.user_address_id
				join sweat16.user_address ba on po.billing_address_id = ba.user_address_id
				join sweat16.subdistrict sd on ba.subdistrict_id = sd.subdistrict_id
				join sweat16.district d using (district_id)
				join sweat16.province p using (province_id)
				join sweat16.shipping s using (shipping_id)
			where po.purchase_order_id = ?
sql
		);

		$query->setInt(1, $record['purchase-order-id']);
		$query->open();

		$rows = $query->getResultArray();

		return (count($rows) == 0)? array(): $rows[0];
	}
}
