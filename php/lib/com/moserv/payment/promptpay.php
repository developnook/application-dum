<?php

require_once('com/moserv/security/crc.php');

/**
 * Inspired and code logic from https://github.com/dtinth/promptpay-qr
 * More information https://www.blognone.com/node/95133
 */
class Promptpay {
	public static $instance = null;

	const ID_PAYLOAD_FORMAT = '00';
	const ID_POI_METHOD = '01';
	const ID_MERCHANT_INFORMATION_BOT = '29';
	const ID_TRANSACTION_CURRENCY = '53';
	const ID_TRANSACTION_AMOUNT = '54';
	const ID_COUNTRY_CODE = '58';
	const ID_CRC = '63';

	const PAYLOAD_FORMAT_EMV_QRCPS_MERCHANT_PRESENTED_MODE = '01';
	const POI_METHOD_STATIC = '11';
	const POI_METHOD_DYNAMIC = '12';
	const MERCHANT_INFORMATION_TEMPLATE_ID_GUID = '00';
	const BOT_ID_MERCHANT_PHONE_NUMBER = '01';
	const BOT_ID_MERCHANT_TAX_ID = '02';
	const BOT_ID_MERCHANT_EWALLET_ID = '03';
	const GUID_PROMPTPAY = 'A000000677010111';
	const TRANSACTION_CURRENCY_THB = '764';
	const COUNTRY_CODE_TH = 'TH';

	public function __construct() {
		Promptpay::$instance = $this;
	}

	public function generatePayload($target, $amount = null) {
		$target = $this->sanitizeTarget($target);
		$targetType = strlen($target) >= 15 ? Promptpay::BOT_ID_MERCHANT_EWALLET_ID : (strlen($target) >= 13 ? Promptpay::BOT_ID_MERCHANT_TAX_ID : Promptpay::BOT_ID_MERCHANT_PHONE_NUMBER);
		$data = [
			$this->f(Promptpay::ID_PAYLOAD_FORMAT, Promptpay::PAYLOAD_FORMAT_EMV_QRCPS_MERCHANT_PRESENTED_MODE),
			$this->f(Promptpay::ID_POI_METHOD, $amount ? Promptpay::POI_METHOD_DYNAMIC : Promptpay::POI_METHOD_STATIC),
			$this->f(Promptpay::ID_MERCHANT_INFORMATION_BOT, $this->serialize([
				$this->f(Promptpay::MERCHANT_INFORMATION_TEMPLATE_ID_GUID, Promptpay::GUID_PROMPTPAY),
				$this->f($targetType, $this->formatTarget($target))
			])),
			$this->f(Promptpay::ID_COUNTRY_CODE, Promptpay::COUNTRY_CODE_TH),
			$this->f(Promptpay::ID_TRANSACTION_CURRENCY, Promptpay::TRANSACTION_CURRENCY_THB),
		];

		if ($amount !== null) {
			array_push($data, $this->f(Promptpay::ID_TRANSACTION_AMOUNT, $this->formatAmount($amount)));
		}

		$dataToCrc = $this->serialize($data) . Promptpay::ID_CRC . '04';

		array_push($data, $this->f(Promptpay::ID_CRC, $this->formatCrc(Crc::perform($dataToCrc, 16))));
#		array_push($data, $this->f(Promptpay::ID_CRC, $this->crc16($dataToCrc)));

		return $this->serialize($data);
	}

	public function f($id, $value) {
		return implode('', [$id, substr('00' . strlen($value), -2), $value]);
	}

	public function serialize($xs) {
		return implode('', $xs);
	}

	public function sanitizeTarget($str) {
		$str = preg_replace('/[^0-9]/', '', $str);
		return $str;
	}

	public function formatTarget($target) {
		$str = $this->sanitizeTarget($target);
		if (strlen($str) >= 13) {
			return $str;
		}

		$str = preg_replace('/^0/', '66', $str);
		$str = '0000000000000' . $str;

		return substr($str, -13);
	}

	public function formatAmount($amount) {
		return number_format($amount, 2, '.', '');
	}

	public function formatCrc($crcValue) {
		return substr('0000'.strtoupper(dechex($crcValue)), -4);
	}

	public static function payload($target, $amount = null) {
		if (Promptpay::$instance == null) {
			Promptpay::$instance = new PromptPay();
		}

		return Promptpay::$instance->generatePayload($target, $amount);
	}
}

##$pp = new PromptPay();
##$payload = Promptpay::payload('0105555035224', 512.25);
##echo "{$payload}\n";
##$payload = Promptpay::payload('0817204478', 512.25);
##echo "{$payload}\n";
