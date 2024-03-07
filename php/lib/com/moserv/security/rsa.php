<?php

require_once('com/blackduck/phpseclib/Crypt/RSA.php');
require_once('com/blackduck/phpseclib/Math/BigInteger.php');


class Rsa {

	const FMT_PEM = 0x00;
	const FMT_XML = 0x01;
	const FMT_SSH = 0x02;

	public static function loadKey($key, $passphrase = null) {
		$contentKey = false;

		if (preg_match('|^file://(.+)|', $key, $group)) {
			list(, $filepath) = $group;

#			echo "#$filepath#";
#			exit;

			if (file_exists($filepath))
				$contentKey = file_get_contents($filepath);
		}
		else
			$contentKey = $key;

		return ($passphrase == null)? $contentKey: openssl_get_privatekey($contentKey, $passphrase);
	}

	public static function encryptByPublicKey($input, $key, $format = Rsa::FMT_PEM) {
		$output = false;
		$contentKey = Rsa::loadKey($key);

		switch ($format) {
			case Rsa::FMT_PEM:
				$output = (openssl_public_encrypt($input, $encrypted, $contentKey))? $encrypted: false;
			break;

			case Rsa::FMT_XML:
				$output = false;
			break;

			case Rsa::FMT_SSH:
				$encryptor = new Crypt_RSA();
				$encryptor->setEncryptionMode(CRYPT_RSA_ENCRYPTION_PKCS1);
				$encryptor->setPublicKeyFormat(CRYPT_RSA_PUBLIC_FORMAT_OPENSSH);
				$encryptor->loadKey($contentKey);
				$output = $encryptor->encrypt($input, $contentKey);
			break;

			default:
				$output = false;
			break;
		}

		return $output;
	}


	public static function decryptByPrivateKey($input, $key, $format = Rsa::FMT_PEM, $passphrase = null) {
		$output = false;
		$contentKey = Rsa::loadKey($key, $passphrase);

		switch ($format) {
			case Rsa::FMT_PEM:
				$output = (openssl_private_decrypt($input, $decrypted, $contentKey))? $decrypted: false;
			break;

			default:
				$output = false;
			break;
		}

		return $output;
	}
}

#for ($i = 0; $i < 100; $i++) {
#	$encrypted = Rsa::encryptByPublicKey("hello test", "file:///home/moserv/test/php/rsa/moserv-public.pem");
#	$encrypted = Rsa::encryptByPublicKey("hello test", "file:///home/moserv/test/php/rsa/moserv-ssh-public.key", Rsa::FMT_SSH);
#}
#$decrypted = Rsa::decryptByPrivateKey($encrypted, "file:///home/moserv/test/php/rsa/moserv-private.pem");
#
#echo "$decrypted\n";

?>
