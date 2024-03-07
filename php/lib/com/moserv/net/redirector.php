<?php

require_once('com/moserv/net/http.php');

class Redirector {

	const rd_http = 0x00;
	const rd_href = 0x01;
	const rd_mref = 0x02;
	const rd_chin = 0x03;

	private $method;
	private $code;
	private $delay;
	private $url;
	private $headers;

	public function __construct() {
		$this->method = Redirector::rd_http;
		$this->code = 303;
		$this->delay = 0;
		$this->url = null;
		$this->headers = array();
	}

	public function setMethod($method) {
		$this->method = $method;
	}

	public function setCode($code) {
		$this->code = $code;
	}

	public function setDelay($delay) {
		$this->delay = $delay;
	}

	public function setUrl($url) {
		$this->url = $url;
	}

	public function execute() {

		global $_SERVER;

		switch ($this->method) {
			case Redirector::rd_http:
				header("Location: {$this->url}", true, $this->code);
			break;

			case Redirector::rd_href:
				header("refresh:{$this->delay};url={$this->url}");
			break;

			case Redirector::rd_mref:
				header("Content-Type: text/html");

				$sleeptime = $this->delay * 1000;

				echo "<noscript><meta http-equiv=\"refresh\" content=\"{$this->delay}; url={$this->url}\" /></noscript>";
				echo "<body onload=\"setTimeout(function() { window.location = '{$this->url}'; }, {$sleeptime});\"></body>";
			break;

			case Redirector::rd_chin:
				$client = new HttpClient();
				$client->setUrl($this->url);
				$client->getHeaders()->add('user_agent', $_SERVER['HTTP_USER_AGENT']);

				foreach ($this->headers as $key => $value) {
					$client->getHeaders()->add($key, $value);
				}

				$client->execute();

				echo $client->getResponse()->getContent();
			break;
		}

		exit();
	}

	public function setHeader($key, $value) {
		$this->headers[$key] = $value;
	}
}

