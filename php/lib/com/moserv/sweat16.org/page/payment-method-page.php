<?php

require_once('com/moserv/sweat16/page/checkout-page.php');
require_once('com/moserv/sweat16/loader/issuer-list-loader.php');
require_once('com/moserv/sweat16/loader/province-list-loader.php');
require_once('com/moserv/sweat16/loader/district-list-loader.php');
require_once('com/moserv/sweat16/loader/subdistrict-list-loader.php');
require_once('com/moserv/sweat16/loader/shipping-list-loader.php');

class PaymentMethodPage extends CheckoutPage {

	protected function doLoad() {
		$session = $this->getSession();
		$capsule = parent::doLoad();

		$loader = new IssuerListLoader();
		$capsule['issuer-list'] = $loader->execute();


#####################################

		$loaders = array(
			'province'	=> new ProvinceListLoader(),
			'district'	=> new DistrictListLoader(),
			'subdistrict'	=> new SubdistrictListLoader()
		);

		$subdistricts	= $loaders['subdistrict']->execute(array('subdistrict-id' => (empty($_SESSION['customer-information']))? 0: $_SESSION['customer-information']['billing--address-sub-district']));
		$districts	= $loaders['district']->execute(array('district-id' => (count($subdistricts) == 0)? 0: $subdistricts[0]['district_id']));
		$provinces	= $loaders['province']->execute(array('province-id' => (count($districts) == 0)? 0: $districts[0]['province_id']));

		$capsule['location'] = array(
			'subdistricts'	=> $subdistricts,
			'districts'	=> $districts,
			'provinces'	=> $provinces
		);
#####################################



		$loader = new ShippingListLoader();
		$capsule['shipping-list'] = $loader->execute(array('user-id' => $session->getVar('user-id')));

		return $capsule;
	}
}
