<?php

require_once('com/moserv/security/rsa.php');

class Serializer {

	public static function encapsulate($input, $publicKey = null) {
		global $_SERVER;

		if ($publicKey == null) {
			$home = dirname(dirname($_SERVER['DOCUMENT_ROOT']));
			$publicKey = "file://{$home}/certs/moserv-rsa-public.pem";
#			$publicKey = "file://{$_SERVER['DOCUMENT_ROOT']}/../../certs/moserv-rsa-public.pem";
		}

		$serialized = json_encode($input);
		$encrypted = Rsa::encryptByPublicKey($serialized, $publicKey);
		$encoded = base64_encode($encrypted);

		return $encoded;

	}

	public static function decapsulate($input, $privateKey = null) {
		global $_SERVER;

		if ($privateKey == null) {
			$home = dirname(dirname($_SERVER['DOCUMENT_ROOT']));
			$privateKey = "file://{$home}/certs/moserv-rsa-private.pem";
#			$privateKey = "file://{$_SERVER['DOCUMENT_ROOT']}/../../certs/moserv-rsa-private.pem";
		}

		$decoded = base64_decode($input);
		$decrypted = Rsa::decryptByPrivateKey($decoded, $privateKey);
		$deserialized = json_decode($decrypted, true);

		return $deserialized;
	}
}

