<?php

require_once('com/moserv/sweat16/loader/loader.php');


class SaveAddressLoader extends Loader {

	protected function doExecute($params = null) {
		global $_SESSION;

		$capsule = $this->loadSaveAddress(array( 'user-id' => $_SESSION['user-id']));

		return $capsule;
	}


	protected function loadSaveAddress($record) {
		$session = $this->getSession();
		$connection = $session->getConnection();

		$query = $connection->createQuery(
<<<sql
			select
				ua.name,
				ua.sur_name		as 'sur-name',
				ua.phone,
				ua.address,
				ua.subdistrict_id	as 'subdistrict-id',
				s.subdistrict_name_th	as 'subdistrict-name',
				d.district_name_th	as 'district-name',
				p.province_name_th	as 'province-name',
				ua.zip_code		as 'zip-code'
			from sweat16.purchase_order po
				join sweat16.cart c using (cart_id)
				join sweat16.user_address ua on po.shipping_address_id = ua.user_address_id
				join sweat16.subdistrict s using (subdistrict_id)
				join sweat16.district d using (district_id)
				join sweat16.province p using (province_id)
			where c.user_id = ?
			order by po.purchase_order_id desc
			limit 1
sql
		);

		$query->setInt(1, $record['user-id']);
		$query->open();

		$rows = $query->getResultArray();

		return (count($rows) == 0)? array(): array(
			'name'			=> $rows[0]['name'],
			'sur-name'		=> $rows[0]['sur-name'],
			'phone'			=> $rows[0]['phone'],
			'address'		=> $rows[0]['address'],
			'subdistrict-id'	=> $rows[0]['subdistrict-id'],
			'subdistrict-name'	=> $rows[0]['subdistrict-name'],
			'district-name'		=> $rows[0]['district-name'],
			'province-name'		=> $rows[0]['province-name'],
			'zip-code'		=> $rows[0]['zip-code']
		);
	}
}
