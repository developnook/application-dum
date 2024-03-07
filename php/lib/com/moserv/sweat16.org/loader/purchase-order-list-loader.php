<?php

require_once('com/moserv/sweat16/loader/loader.php');


class PurchaseOrderListLoader extends Loader {

	protected function doExecute($params = null) {
		$session = $this->getSession();
		$connection = $session->getConnection();

		$query = $connection->createQuery(
<<<sql
			select
				po.purchase_order_id as value,
				concat(
					po.po_code,
					' [',
					date_format(po.sys_timestamp, '%Y-%m-%d %H:%i'),
					']  ',
					lpad(format(coalesce(po.cart_price + po.shipping_price, 0), 2) , 10, '_'),
					' บาท',
					if(_x.informs is null, '', concat(' - แจ้งแล้ว (', _x.informs, ')'))
				) as title,
				coalesce(informs, 0) as informs
			from sweat16.purchase_order po
				join sweat16.cart c using (cart_id)
				left join (
					select
						_po.purchase_order_id,
						count(*) as informs
					from sweat16.purchase_order _po
						join sweat16.payment_by_transfer _pbt using (purchase_order_id)
						join cart _c using (cart_id)
					where _c.user_id = ?
					and _po.payment_type_id = 1
					and _po.status = 0
					group by _po.purchase_order_id
				) _x using (purchase_order_id)
			where c.user_id = ?
			and po.payment_type_id = 1
			and po.status = 0
			group by po.purchase_order_id, title
			order by po.purchase_order_id desc
sql
		);

		$query->setInt(1, $session->getVar('user-id'));
		$query->setInt(2, $session->getVar('user-id'));

		$query->open();

		$rows = $query->getResultArray();

		return $rows;
	}
}
