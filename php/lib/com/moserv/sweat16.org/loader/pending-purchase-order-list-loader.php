<?php

require_once('com/moserv/sweat16/loader/loader.php');


class PendingPurchaseOrderListLoader extends Loader {

	protected function doExecute($params = null) {
		$session = $this->getSession();
		$connection = $session->getConnection();

		$query = $connection->createQuery(
<<<sql
			select
				po.sys_timestamp as 'po-timestamp',
				concat(u.name, ' ', u.surname) as name,
				u.email,
				pbt.sys_timestamp as 'inform-timestamp',
				concat(
					lpad(pbt.year, 4, 0), '-',
					lpad(pbt.month, 2, 0), '-',
					lpad(pbt.day, 2, '0'), ' ',
					lpad(pbt.hour, 2, '0'), ':',
					lpad(pbt.minute, 2, '0')
				)
			from sweat16.purchase_order po
				join sweat16.cart c using (cart_id)
				join sweat16.user u using (user_id)
				join sweat16.payment_by_transfer pbt on pbt.payment_by_transfer_id = (
					select
						_pbt.payment_by_transfer_id
					from payment_by_transfer _pbt
					where _pbt.purchase_order_id = po.purchase_order_id
					order by _pbt.payment_by_transfer_id desc
					limit 1
				)
			where po.sys_timestamp >= date_sub(current_timestamp(), interval 5 day)
			and po.status = 0
			and po.payment_type_id = 1
sql
		);

		$json = file_get_contents('https://tep.paysolutions.asia/api/v1/bank/v1/th');
		$list = json_decode($json, true);

		return array('rows' => $list['data']);
	}
}
