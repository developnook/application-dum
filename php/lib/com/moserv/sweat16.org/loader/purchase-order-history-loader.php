<?php

require_once('com/moserv/sweat16/loader/loader.php');


class PurchaseOrderHistoryLoader extends Loader {

	protected function doExecute($params = null) {
		$session = $this->getSession();
		$connection = $session->getConnection();

		$query = $connection->createQuery(
<<<sql
			select
				po.purchase_order_id					as 'purchase-order-id',
				po_code							as 'po-code',
				po.cart_price						as 'price',
				po.payment_type_id					as 'payment-type-id',
				po.status,
				date(po.sys_timestamp)					as 'purchase-timestamp',
				date(if(po.status = - 1, date_add(po.sys_timestamp, interval 72 hour), coalesce(pbc.sys_timestamp, pbt.sys_timestamp)))	as 'payment-timestamp',
				date(ps.shipping_timestamp)				as 'shipping-timestamp'
			from sweat16.purchase_order po
				join sweat16.cart c using (cart_id)
				join sweat16.payment_type pt using (payment_type_id)
				left join sweat16.payment_by_card pbc on pbc.payment_by_card_id = (
					select
						_pbc.payment_by_card_id
					from sweat16.payment_by_card _pbc
					where _pbc.purchase_order_id = po.purchase_order_id
					order by _pbc.payment_by_card_id desc
					limit 1
				)
				left join sweat16.payment_by_transfer pbt on pbt.payment_by_transfer_id = (
					select
						_pbt.payment_by_transfer_id
					from sweat16.payment_by_transfer _pbt
					where _pbt.purchase_order_id = po.purchase_order_id
					order by _pbt.payment_by_transfer_id desc
					limit 1
				)
				left join sweat16.po_shipping ps on ps.po_shipping_id = (
					select
						_ps.po_shipping_id
					from sweat16.po_shipping _ps
					where _ps.purchase_order_id = po.purchase_order_id
					order by _ps.po_shipping_id
					limit 1
				)
			where c.user_id = ?
			order by po.purchase_order_id desc
sql
		);

		$query->setInt(1, $session->getVar('user-id'));

		$query->open();

		$rows = $query->getResultArray();

		return array('rows' => $rows);
	}
}
