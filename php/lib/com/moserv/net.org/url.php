<?php

require_once('com/moserv/net/http.php');

class Url {

	const SERVICE_FILE	= '/etc/services';

	const TOK_SCHE		= 0x00;
	const TOK_USER		= 0x01;
	const TOK_HOST		= 0x02;
	const TOK_PORT		= 0x03;
	const TOK_PATH		= 0x04;
	const TOK_QURY		= 0x05;
	const TOK_FRAG		= 0x06;

	const TOK_COUT		= 7;

	public static $TOK_NAME = array(
		'scheme',
		'user',
		'host',
		'port',
		'path',
		'query',
		'fragment'
	);

	const RDR_HTTP_301	= 0x00;
	const RDR_HTTP_302	= 0x02;
	const RDR_HTTP_303	= 0x03;
	const RDR_HTTP_307	= 0x04;
	const RDR_HTTP_REF	= 0x05;
	const RDR_META_TAG	= 0x06;
	const RDR_JAVA_SRC	= 0x07;

	public static $RDR_TEXT = array(
		'HTTP/1.1 301 Moved Permanently',
		'',
		'',
		''
	);


	protected $token;

	public function __construct($line = null) {

		if ($line == null)
			$line = Url::getCurrentUrl();

		$this->token = Url::parse($line);
	}

	public function getScheme() {
		return $this->token['scheme'];
	}

	public function getUser() {
		return $this->token['user'];
	}

	public function getPass() {
		return $this->token['pass'];
	}

	public function getHost() {
		return $this->token['host'];
	}

	public function getPort() {
		return $this->token['port'];
	}

	public function getPath() {
		return $this->token['path'];
	}

	public function getQuery() {
		return $this->token['query'];
	}

	public function getFragment() {
		return $this->token['fragment'];
	}

	public function setScheme($scheme) {
		$this->token['scheme'] = $scheme;
	}

	public function setUser($user) {
		$this->token['user'] = $user;
	}

	public function setPass($pass) {
		$this->token['pass'] = $pass;
	}

	public function setHost($host) {
		$this->token['host'] = $host;
	}

	public function setPort($port) {
		$this->token['port'] = $port;
	}

	public function setPath($path) {
		$this->token['path'] = $path;
	}

	public function setQuery($query) {
		$this->token['query'] = $query;
	}

	public function setFragment($fragment) {
		$this->fragment = $fragment;
	}

	public function redirect($suffix = null) {
		global $_SERVER;

		if (array_key_exists('HTTP_USER_AGENT', $_SERVER) && $_SERVER['HTTP_USER_AGENT'] == HttpClient::USER_AGENT) {
			$xurl = htmlspecialchars($this->toString());

			echo
<<<xml
				<?xml version="1.0"?>
				<hawhaw>
					<deck title="Redirect" redirection="0; URL={$xurl};proxy=no" />
				</hawhaw>
xml
			;
		}
		else {
			if ($suffix == null) {
				$line = $this->toString();
			}
			else {
				$surl = new Url($suffix);
				$clone = clone $this;
				$index = 0;

				while ($index < Url::TOK_COUT && empty($surl->getToken($index))) {
					$index++;
				}

				while ($index < Url::TOK_COUT && !empty($surl->getToken($index))) {
					$clone->setToken($index, $surl->getToken($index));
					$index++;
				}

				$line = $clone->toString();
			}

			header("location: $line");
		}

		exit;
	}

#	public function toString($inline = true) {
#		return ($inline)? $this->unparse($this->token): print_r($this->token, true);
#	}

	public function toString($lastTok = Url::TOK_COUT) {
		return $this->unparse($this->token, $lastTok);
	}

	public function setParam($key, $value = null) {
		if ($value == null)
			unset($this->token['params'][$key]);
		else
			$this->token['params'][$key] = $value;
	}

	public function setParams($params, $append = false) {
		if ($append) {
			if ($this->token['params'] == null)
				$this->token['params'] = array();

			foreach ($params as $key => $value) {
				$this->token['params'][$key] = $value;
			}
		}
		else
			$this->token['params'] = $params;
	}

	public function getParam($key) {
		return $this->token['params'][$key];
	}

	public function getParams() {
		return $this->token['params'];
	}
/*
	public function setPath($path) {
		$this->token['path'] = $path;
	}

	public function getPath($path = null) {
		if (preg('|^/|', $path)) {
			return $path;
		}
		elseif (preg_match('|/$|', $path)) {
			return "{$this->token['path']}{$path}";
		}
		else {
			return dirname($this->token['path'])."/{$path}";
		}
	}
*/
	public function getToken($index = -1) {
		return ($index >= 0 && $index < Url::TOK_COUT)? $this->token[Url::$TOK_NAME[$index]]: $this->token;
	}

	public function setToken($index, $value) {
		$this->token[Url::$TOK_NAME[$index]] = $value;
	}

	public function cloning() {
		$url = new Url($this->toString());

		return $url;
	}

	public static function getCurrentUrl() {
		global $_SERVER;

		$scheme		= (array_key_exists('HTTPS', $_SERVER) && $_SERVER['HTTPS'] == 'on')? 'https': 'http';
		$host		= $_SERVER['SERVER_NAME'];
		$port		= $_SERVER['SERVER_PORT'];
		$uri		= $_SERVER['REQUEST_URI'];
		$line		= "{$scheme}://{$host}:{$port}{$uri}";

		return $line;
	}


	public static function findProtocol($scheme, $serviceFile = Url::SERVICE_FILE) {
		$protocol = array();
		$pattern = '|^('.preg_quote($scheme, '|').')\s+([0-9]+)/([a-z0-9_-]+)|im';

		if (preg_match($pattern, file_get_contents($serviceFile), $selects)) {

			list(, $protocol['name'], $protocol['port'], $protocol['type']) = $selects;
		}

		return $protocol;
	}

	public static function parse($line) {
		$token = array();

		if (preg_match('|^(?:([a-z0-9_-]+)://)?(?:([^:]+)?:([^@]+)@)?([^:/?#]+)?(?::([0-9]+))?((?:(?:/[^/?]*))*)?(?:\?([^#]+))?(?:#(.*))?$|', $line, $elements)) {

			list(
				$token['line'],
				$token['scheme'],
				$token['user'],
				$token['pass'],
				$token['host'],
				$token['port'],
				$token['path'],
				$token['query'],
				$token['fragment']
			) = array_pad($elements, 9, '');

			parse_str($token['query'], $token['params']);

			if (($token['protocol'] = Url::findProtocol($token['scheme'])) && empty($token['port'])) {
				$token['port'] = $token['protocol']['port'];
			}
		}


		return $token;
	}

	public static function getTokenLine($index, $elem) {
		$token = array();

		switch ($index) {
			case Url::TOK_SCHE:
				if (!empty($elem['scheme'])) {
					$token[] = $elem['scheme'];
					$token[] = '://';
				}
			break;

			case Url::TOK_USER:
				if (!empty($elem['user'])) {
					$token[] = $elem['user'];

					if (!empty($elem['pass'])) {
						$token[] = ':';
						$token[] = $elem['pass'];
					}

					$token[] = '@';
				}
			break;

			case Url::TOK_HOST:
				if (!empty($elem['host'])) {
					$token[] = $elem['host'];
				}
			break;

			case Url::TOK_PORT:
				if (!empty($elem['port']) && $elem['port'] != $elem['protocol']['port']) {
					$token[] = ':';
					$token[] = $elem['port'];
				}
			break;

			case Url::TOK_PATH:
				if (!empty($elem['path'])) {
					$token[] = $elem['path'];
				}
			break;

			case Url::TOK_QURY:
				if (!empty($elem['params'])) {
					$token[] = '?';
					$token[] = http_build_query($elem['params']);
				}
			break;

			case Url::TOK_FRAG:
				if (!empty($elem['fragment'])) {
					$token[] = '#';
					$token[] = $elem['fragment'];
				}
			break;
		}

		return implode('', $token);
	}

	public static function unparse(&$elem, $lastTok = Url::TOK_COUT) {

		$token = array();

		for ($index = 0; $index < Url::TOK_COUT; $index++) {
			$token[] = Url::getTokenLine($index, $elem);

			if ($index >= $lastTok) {
				break;
			}
		}

		$elem['line'] = implode('', $token);

		return $elem['line'];
	}
}

