<?php

class CryptPublicAes {
	function __construct(){
		$this->cipher = "aes-256-cbc";
		$this->options = OPENSSL_RAW_DATA ;
		$this->hashAlgo = "sha512";
		$this->ivBlockLength = 30;
		$this->hmacLength = 88;
	}

	public function EncryptAes($data = null, $key = null) {
		$key = substr(hash("sha256", $key, true), 0, 32);
		$ivlen = openssl_cipher_iv_length($this->cipher);
		$ivKey = $this->generateIv($this->ivBlockLength);
		$iv = substr($ivKey, 0, $ivlen);
		$cipherTextRaw = openssl_encrypt($data, $this->cipher, $key, $this->options, $iv);
		$cipherTextBase = base64_encode($cipherTextRaw);
		$hmac = hash_hmac($this->hashAlgo, $cipherTextBase, $key);
		$sig = hex2bin(strtoupper($hmac));
		$signature = base64_encode($sig);
		$ciphertext = $ivKey.$signature.$cipherTextBase;
		return $ciphertext;
	}

	public function generateIv($length = 10) {
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$charactersLength = strlen($characters);
		$randomString = '';
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[rand(0, $charactersLength - 1)];
		}
		return $randomString;
	}
}
