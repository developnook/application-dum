<?php

require_once('com/moserv/net/url.php');
require_once('com/moserv/sweat16/servlet/servlet.php');
require_once('com/moserv/sweat16/controller/place-order-controller.php');
require_once('com/moserv/sweat16/controller/payment-request-controller.php');
require_once('com/paysolution/CryptPublicAes.php');

class PlaceOrderServlet extends Servlet {

	protected function gotoPaymentGateway($record) {
		$data = array(
#			'merchantID'	=> '00000500',
			'merchantID'	=> '78015158',
			'referenceNo'	=> $record['reference-no'],
			'cardType'	=> $record['card-type'],
			'cardHolder'	=> $record['card-holder'],
			'cardNo'	=> $record['card-no'],
			'cardExpire'	=> $record['card-expire'],
			'secureCode'	=> $record['secure-code'],
			'cardIssuer'	=> $record['card-issuer'],
			'otherIssuer'	=> '0',
			'countryCode'	=> 'TH',
			'customerEmail'	=> $record['customer-email'],
			'productDetail' => $record['product-detail'],
			'total'		=> $record['total'],
			'currencyCode'	=> '00',
			'postBackURL'	=> $record['post-back-url'],
			'returnURL'	=> $record['return-url']
		);

#		print_r($data);
#		print_r($record);
#		exit;

#		$secretKey = '8oeirujsoienusk2947h';
		$secretKey = 'f5b17682418016d14c2c';
		$aes = new CryptPublicAes();
		$enc = $aes->EncryptAes(
			json_encode($data),
			$secretKey
		);

#		$oauth = 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6ImU1YzM4MDVkMDI0ZGMxMTYyNWMwMDk3MTJiODBhZjhhYzNjMjc3M2Q0ZDI5ZmFmMmU5MmM0MWQyZTA2M2M4ZDYxMzUyYjBhYTE0YWEwOWI5In0.eyJhdWQiOiIyIiwianRpIjoiZTVjMzgwNWQwMjRkYzExNjI1YzAwOTcxMmI4MGFmOGFjM2MyNzczZDRkMjlmYWYyZTkyYzQxZDJlMDYzYzhkNjEzNTJiMGFhMTRhYTA5YjkiLCJpYXQiOjE1Mjg3MDMxNjgsIm5iZiI6MTUyODcwMzE2OCwiZXhwIjoxNjg2NDY5NTY4LCJzdWIiOiIwMDAwMDUwMCIsInNjb3BlcyI6W119.mDKG2AjSNEdU2lcAC3On7f8itIko0avdDFVUf3oEQITn5yqAKryTnnSUyhFBiFHNPRDNCNSetHR9B2w1jyGR3go417dkY8cIF5dTwvVnHVa-c48q-bpY2IlNBAE93NvnrbAISx9lT_B2oKiqcgKYqrNhfQVfOjH-Lvt2pXVjC2LggJjl-CQwJqRp90GfD6sywrshuw6l-ura9cYOoFeL2biR8BILby6g9nOSO0DUrVPTtbiDU6RKarSIJM4ByjsvN2Qw_eLXbBwd748l1FZMDS3DmyR4dr5-yjQ5ViR-64BaaCraTY5EdutJDuv7WUL3x8hxiSJld1jEeAPjDmIS_SMWCPQTgKC-1hJ93gpbfDNTMJ29FmISFGE-LuGyy9Vdd8tPNFi_8TSxikAY4hkMHHgYgsZFBzpJO_c0csCqU5kJwgR1RNqqNaR4nqV2MMHwaSovp20bxP14toKrjE9z8OiWkm9nOZ0cZ3tpxHKbvOla_JHWcyn9D6QEcTGxPze6Kpvchzv9jO6e6z7AALthk2o9-YEBhrpYAAQcOBAOs4GCjnOzkvEeK1SFVL_Mk_M1VWl-ADb-c4q9u9XT_Cu2TJ77aezpfFYGas2Mbl1q_wfviQjYc_hYwJLXIQE72oURgBz-yM_4tXm-wsgsumoUUofWmpakXMG0cP8W9AQIQSI';

		$oauth = 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6IjdkMGRiM2FjNWRlZTEyZDY2ODUzN2M0NjA4MThjNWYxYTcwZmY3MGU5MmEzMDQ2OGE5ZTA5M2EwOTIzMDZkOGFhNTVhOTVjM2RlNTEzNDkyIn0.eyJhdWQiOiIyIiwianRpIjoiN2QwZGIzYWM1ZGVlMTJkNjY4NTM3YzQ2MDgxOGM1ZjFhNzBmZjcwZTkyYTMwNDY4YTllMDkzYTA5MjMwNmQ4YWE1NWE5NWMzZGU1MTM0OTIiLCJpYXQiOjE1NDQxNzkxMTYsIm5iZiI6MTU0NDE3OTExNiwiZXhwIjoxNzAxOTQ1NTE2LCJzdWIiOiI3ODAxNTE1OCIsInNjb3BlcyI6W119.nJUp8vQz_emiOGhRgE1vL28AWtbFC4dtO3EXB-Xc1onw9EeDDssWuu6LLHe7aklwk8t1bLH41csopdsXwCR1k8mFlKtuaoffHx8058pOCC-md0QpeAm3-eCva-gJb5mobb1NUEVKrtwGs1BqpX0fQaVFdE2fBj9wSNVq_107QNM6nIsN4GAHbdflTvr_cXN-f_lxMmoOmIM-9Py2l4GqFYGE_Ez6EwF7Ee_nTBXi3wjwsPFUiXKtaeLN96x81He8mGyn_Yt_KQi92b0P4y-oLGVPdFrTVovxMZQdom1WLxOMMa3Q-clt8PYBsNNxcVj96ZMXrwJIrnxfj0mFiitVGwIgDU3QMpb5rXLgYYHuEXo0n393_sXBDpa_Fo_FlIRceN_Si0UmSRARHShtbnIkNGb_eDjykhoJGzba4SIJpa9lAnGcUy3q_d1Q1qYm73ujdbFEuf_9Sn2ny8aRPGsyrQT9OekiTk-LuzCH_swS5f4UsdbL73LMRwGGj5TtnyfwMp3I298kqUGBzKRPjh-3r76DeqPjrWp8yBViuCl6BUzhQET9bk1j99tMAEbGi1IIZElnXQy8d1x9p6DvvB--CglK7cejcjJ4g8fzCjTVk7xq3kMDRnp_FhjQciTPum3-znuv5JgW5ULQCi4BLN08gtKb_qK5JE7vXoLq1cXkx7c';
		

		$paymentUrl = new Url('https://tep.paysolutions.asia/api/v1/securepayment');

		$controller = new PaymentRequestController();
		$controller->setInputParams(
			array(
				'payment-by-card-id'	=> $record['payment-by-card-id'],
				'params'		=> json_encode(array(
					'data'		=> $data,
					'secret-key'	=> $secretKey,
					'oauth'		=> $oauth,
					'url'		=> $paymentUrl->toString()
				))
			)
		);

		$controller->execute();


		echo
<<<html
			<form action="{$paymentUrl->toString()}" method="POST" name="paymentRequestForm">
				<input type="hidden" name="reqPay" value="{$enc}" />
				<input type="hidden" name="Authorization" value="{$oauth}" />
			</form>

			<script type="text/javascript"> document.paymentRequestForm.submit(); </script>
html
		;

	}

	public function execute() {
		global $_POST;
		global $_SESSION;

		$_SESSION['customer-information']['billing-address-use']	= $_POST['billing-address-use'];
		$_SESSION['customer-information']['billing-name']		= $_POST['billing-name'];
		$_SESSION['customer-information']['billing-sur-name']		= $_POST['billing-sur-name'];
		$_SESSION['customer-information']['billing-phone']		= $_POST['billing-phone'];
		$_SESSION['customer-information']['billing-sub-district-id']	= $_POST['billing-sub-district-id'];
		$_SESSION['customer-information']['billing-zip-code']		= $_POST['billing-zip-code'];

		$_SESSION['customer-information']['payment-type-id']		= $_POST['payment-type-id'];
		$_SESSION['customer-information']['payment-card-no']		= $_POST['payment-card-no'];
		$_SESSION['customer-information']['payment-card-name-on-card']	= $_POST['payment-card-name-on-card'];
		$_SESSION['customer-information']['payment-card-exp']		= $_POST['payment-card-exp'];
		$_SESSION['customer-information']['payment-card-cvv']		= $_POST['payment-card-cvv'];


//		echo "A-baddress-use : " .  $_POST['billing-address-use'] . ",";
//		echo "A-bname : " .  $_POST['billing-name'] . ",";
//		echo "A-bsurname : " .  $_POST['billing-sur-name'] . ",";
//		echo "A-bphone : " .  $_POST['billing-phone'] . ",";
//		echo "A-bsubdis : " .  $_POST['billing-sub-district-id'] . ",";
//		echo "A-bzipcode : " .  $_POST['billing-zip-code'] . ",";
//
//		echo "A-ptypeid : " .  $_POST['payment-type-id'] . ",";
//		echo "A-pcardon : " .  $_POST['payment-card-no'] . ",";
//		echo "A-pcardname : " .  $_POST['payment-card-name-on-card'] . ",";
//		echo "A-pcardexp : " .  $_POST['payment-card-exp'] . ",";
//		echo "A-pcardcvv : " .  $_POST['payment-card-cvv'];
//
//		exit;

		$controller = new PlaceOrderController();

		$controller->setInputParams($_POST);

		$controller->execute();

		$record = $controller->getOutputParams();

		$_SESSION['last-purchase-order-id'] = $record['purchase-order-id'];
		$_SESSION['last-po-code'] = $record['po-code'];

//		print_r($record);
//		exit;


		switch ($_POST['payment-type-id']) {

			case 1: # money transfer
				unset($_SESSION['cart']);
				unset($_SESSION['customer-information']);

				$this->redirect('/checkout/wait-transfer/');

				exit;
			break;

			case 2: # credit/debit card
				$postBackUrl = new Url();
				$postBackUrl->setPath('/checkout/do-payment-postback/');

				$returnUrl = new Url();
				$returnUrl->setPath('/checkout/do-payment-return/');

				$this->gotoPaymentGateway(
					array(
						'payment-by-card-id'	=> $record['payment-by-card-id'],
						'reference-no'		=> $record['payment-by-card-id'],
						'card-type'		=> $_POST['payment-card-type'],
						'card-holder'		=> $_POST['payment-card-name-on-card'],
						'card-no'		=> $_POST['payment-card-no'],
#						'card-expire'		=> str_replace('/', '', $_POST['payment-card-exp']),
						'card-expire'		=> (preg_match('/^([0-9]{2})\/([0-9]{2})$/', $_POST['payment-card-exp'], $group))? "{$group[1]}20{$group[2]}": '000000',
						'secure-code'		=> $_POST['payment-card-cvv'],
						'card-issuer'		=> $_POST['payment-card-issuer'],
						'customer-email'	=> $_SESSION['user-email'],
#						'product-detail'	=> sprintf('SWT%05d', $record['purchase-order-id']),
						'product-detail'	=> "Sweat16! Shop {$record['po-code']}",
						'total'			=> $record['cart-total-price'] + $_SESSION['customer-information']['shipping-price'],
						'post-back-url'		=> $postBackUrl->toString(),
						'return-url'		=> $returnUrl->toString()
					)
				);

				exit;
			break;
		}
	}
}

