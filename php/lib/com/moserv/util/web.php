<?php

require_once('com/moserv/net/url.php');


class Web {

	public static $random = 0;
	public static $namespace = null;

	public static function generateRandom() {
		if (self::$random == 0)
			self::$random = rand(1000000, 9999999);

		return self::$random;
	}

	public static function includeCss($link) {
		$random = self::generateRandom();

		echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"$link?$random\" />\n";
	}

	public static function includeJs($link) {
		$random = self::generateRandom();

		echo "<script type=\"text/javascript\" src=\"$link?$random\"></script>\n";
	}

	public static function curPageUrl($incPath = true) {
		global $_SERVER;

		$pageURL = 'http';

		if (array_key_exists('HTTPS', $_SERVER) && $_SERVER['HTTPS'] == 'on')
			$pageURL .= 's';

		$pageURL .= '://';
		$pageURL .= $_SERVER['SERVER_NAME'];

		if ($_SERVER['SERVER_PORT'] != '80' && $_SERVER['SERVER_PORT'] != '443')
			$pageURL .= ':' . $_SERVER['SERVER_PORT'];

		if ($incPath)
			$pageURL .= $_SERVER['REQUEST_URI'];

		return $pageURL;
	}

	public static function redirect($path) {
		$url = Web::curPageUrl(false) . $path;

		header("location: $url");
		exit;
	}

	public static function json_unescape($string) {
		return preg_replace_callback(
			'/%u([0-9a-f]{4})/i',

			function($match) {
				return mb_convert_encoding(
					pack('H*', $match[1]),
					'UTF-8',
					'UCS-2BE'
				);
			},
			$string
		);
	}

	public static function getMimeType($fileName, $mimePath = "/usr/project/conf/apache/mime.types") {
		if (($ext = pathinfo($fileName, PATHINFO_EXTENSION)) == '') 
			return false;
		
		$content = file_get_contents($mimePath);
		$pattern = '/(?:^|\r?\n)[\t ]*([^\t\r\n #]+)(?:[\t ]+[^\r\n\t #]+)*?[\t ]+'.preg_quote($ext, '/').'(\s|$)/i';
	
		return (preg_match($pattern, $content, $matches))? $matches[1]:false;
	}

	public static function getNamespace() {
		if (Web::$namespace == null) {

			$url = new Url();
			$host = $url->getHost();

			$tokens	= explode('.', $host);
			$rtokens = array_reverse($tokens);
			Web::$namespace = implode('.', $rtokens);
		}

		return Web::$namespace;
	}

	public static function getCfgVar($varName, $default = 'th.co.moserv.m') {
		$namespace = Web::getNamespace();

		if (
			($value = get_cfg_var("{$namespace}.{$varName}")) === FALSE &&
			($value = get_cfg_var("{$default}.{$varName}")) === FALSE
		) {
			$value = null;
		}

		return $value;
	}

	public static function log($line, $filename = null) {
		$timestamp = (new DateTime)->format('Y-m-d H:i:s.u');
		$url = new Url();

		if ($filename == null) {
			if (($host = $url->getHost()) == '') {
				$filename = '/tmp/default.log';
			}
			else {
				$filename = "/tmp/{$host}.log";
			}
		}

		$data = "{$timestamp} - {$line}\n";

		file_put_contents($filename, $data, FILE_APPEND);
	}
}
