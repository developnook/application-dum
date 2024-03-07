<?php

require_once('com/moserv/sweat16/page/checkout-page.php');
require_once('com/moserv/sweat16/loader/province-list-loader.php');
require_once('com/moserv/sweat16/loader/district-list-loader.php');
require_once('com/moserv/sweat16/loader/subdistrict-list-loader.php');
require_once('com/moserv/sweat16/loader/save-address-loader.php');

class CustomerInformationPage extends CheckoutPage {

	protected function doLoad() {
		global $_SESSION;

		$capsule = parent::doLoad();

		$loader = new SaveAddressLoader();
		$capsule['save-address'] = $loader->execute();


		$loaders = array(
			'province'	=> new ProvinceListLoader(),
			'district'	=> new DistrictListLoader(),
			'subdistrict'	=> new SubdistrictListLoader()
		);

		$subdistricts	= $loaders['subdistrict']->execute(
			array('subdistrict-id' =>
				((!empty($_SESSION['customer-information']))? $_SESSION['customer-information']['sub-district-id']:
						((!empty($capsule['save-address']['subdistrict-id']))? $capsule['save-address']['subdistrict-id']:
							0
				))
			)
		);
		$districts	= $loaders['district']->execute(array('district-id' => (count($subdistricts) == 0)? 0: $subdistricts[0]['district_id']));
		$provinces	= $loaders['province']->execute(array('province-id' => (count($districts) == 0)? 0: $districts[0]['province_id']));

		$capsule['location'] = array(
			'subdistricts'	=> $subdistricts,
			'districts'	=> $districts,
			'provinces'	=> $provinces
		);

		return $capsule;
	}
}
