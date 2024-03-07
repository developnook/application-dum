<?php

require_once('com/moserv/sweat16/loader/loader.php');


class OrderTrackingLoader extends Loader {

	protected function doExecute($params = null) {
		$session = $this->getSession();
		$connection = $session->getConnection();

		$query = $connection->createQuery(
<<<sql
			select
				replace(s.tracking_url, '{tracking-number}', ps.tracking_number) as 'tracking-url'
			from sweat16.purchase_order po
				join sweat16.cart c using (cart_id)
				join sweat16.po_shipping ps using (purchase_order_id)
				join sweat16.shipping s on s.shipping_id = ps.shipping_id
			where po.purchase_order_id = ?
			and c.user_id = ?
			and po.status >= 2
			and ps.tracking_number is not null
sql
		);

		$query->setInt(1, $params['purchase-order-id']);
		$query->setInt(2, $session->getVar('user-id'));

		$query->open();

		$rows = $query->getResultArray();

		return $rows;
	}
}
