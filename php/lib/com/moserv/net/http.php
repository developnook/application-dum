<?php

require_once('com/moserv/log/logger.php');

class HttpParam {

	protected $name;
	protected $value;

	public function __construct($name = null, $value = null) {
		$this->name = $name;
		$this->value = $value;
	}

	public function getName() {
		return $this->name;
	}

	public function setName($name) {
		$this->name = $name;
	}

	public function getValue() {
		return $this->value;
	}

	public function setValue($value) {
		$this->value = $value;
	}

	public function toString($sep = '=') {
		$name = $this->name;
		$value = urlencode($this->value);

		return "$name$sep$value";
	}
}

class HttpHeader extends HttpParam {

	public function toString($sep = ': ') {
		$name = $this->name;
		$value = $this->value;

		return "$name$sep$value";
	}
}

class HttpParamList {

	protected $params;

	protected function createParam() {
		return new HttpParam();
	}

	public function __construct() {
		$this->params = array();
	}

	public function add($name, $value) {
		$param = $this->createParam();

		$param->setName($name);
		$param->setValue($value);

		$this->params[] = $param;

		return count($this->params);
	}

	public function get($index) {
		return $this->params[$index];
	}

	public function count() {
		return count($this->params);
	}

	public function clear() {
		$this->params = array();
	}

	public function toString($sep = '&') {
		$params = array();

		for ($index = 0; $index < $this->count(); $index++) {
			$param = $this->get($index);

			$params[] = $param->toString();
		}

		$result = implode($sep, $params);

		return $result;
	}
}

class HttpHeaderList extends HttpParamList {

	protected function createParam() {
		return new HttpHeader();
	}

	public function toString($sep = "\r\n") {
		$result = parent::toString($sep);

		return $result;
	}
}

class HttpResponse {

	protected $rawHeaders;
	protected $headers;
	protected $content;

	protected $version;
	protected $code;
	protected $message;

	public function __construct($rawHeaders, $content) {
		$this->rawHeaders = $rawHeaders;
		$this->content = $content;

		$this->parseRawHeaders();
	}

	public function parseRawHeaders() {
		$this->headers = new HttpHeaderList();

		for ($index = 0; $index < count($this->rawHeaders); $index++) {
			$line = $this->rawHeaders[$index];
			if (preg_match('/^HTTP\/([^ ]+) +([0-9]+) +(.*)$/', $line, $group)) {
				$this->version = $group[1];
				$this->code = $group[2];
				$this->message = $group[3];
			}
			elseif (preg_match('/^([^:]+): *(.*)$/', $line, $group)) {
				$name = $group[1];
				$value = $group[2];

				$this->headers->add($name, $value);
			}
		}
	}

	public function getRawHeaders() {
		return $this->rawHeaders;
	}

	public function getHeaders() {
		return $this->headers;
	}

	public function getContent() {
		return $this->content;
	}

	public function getVersion() {
		return $this->version;
	}

	public function getCode() {
		return $this->code;
	}

	public function getMessage() {
		return $this->message;
	}
}


class HttpClient {

	const USER_AGENT = 'Moserv HTTP Client 1.0';

	const MTHD_GET  = 0x00;
	const MTHD_POST = 0x01;
	const MTHD_HEAD = 0x02;
	const MTHD_PUT  = 0x03;
	const MTHD_REST = 0x04; 

	public static $methods = array('GET', 'POST', 'HEAD', 'PUT', 'REST');

	protected $bindAddress;

	protected $url;
	protected $params;
	protected $method;
	protected $contentData;

	protected $headers;
	protected $response;

	protected $timeout;

	public function __construct() {
		$this->bindAddress = null;
		$this->params = new HttpParamList();
		$this->headers = new HttpHeaderList();
		$this->responseHeaders = null;

		$this->method = self::MTHD_GET;

		$this->responseCode = null;
		$this->responseData = null;
		$this->contentData = null;

		$this->timeout = 60;
	}

	public function getParams() {
		return $this->params;
	}

	public function getHeaders() {
		return $this->headers;
	}

	public function setUrl($url) {
		$this->url = $url;
	}

	public function getUrl() {
		return $this->url;
	}

	public function setMethod($method) {
		$this->method = $method;
	}

	public function getMethod() {
		return $this->method;
	}

	protected function getContextArray() {
		global $_SERVER;

		$bindAddress	= ($this->bindAddress == null)? $_SERVER['SERVER_ADDR']: $this->bindAddress;
		$method		= self::$methods[$this->method];
		$header		= $this->headers->toString();
		$content	= $this->contentData;
		$timeout	= $this->timeout;

		$opts = array();

		$opts['http'] = array(
			'method'  => $method,
			'header'  => $header,
			'content' => $content,
			'timeout' => $timeout
		);

		if (!empty($bindAddress)) {
			$opts['socket'] = array(
				'bindto' => "{$bindAddress}:0"
			);
		}

#		$opts = array(
#			'socket' => array(
#				'bindto'	=> $bindAddress
#			),
#			'http' => array(
#				'method'	=> $method,
#				'header'	=> $header,
#				'content'	=> $content,
#				'timeout'	=> $timeout
#			)
#		);

#		print_r($opts);

		return $opts;
	}


	public function execute() {
		$fullUrl = $this->url;

		if ($this->method == self::MTHD_GET) {
			$sep = (strpos($this->url, '?') === false)? '?': '&';
			$fullUrl = $this->url . $sep . $this->params->toString();
		}


		$opts = $this->getContextArray();

#		if ($this->method == self::MTHD_POST && $this->contentData != null) {
#			$opts['content'] = $this->contentData;
#		}

		$context = stream_context_create($opts);

		$content = file_get_contents($fullUrl, false, $context);

		$this->response = new HttpResponse($http_response_header, $content);
	}

	public function getResponse() {
		return $this->response;
	}

	public function getContentData() {
		return $this->contentData;
	}

	public function getBindAddress() {
		return $this->bindAddress;
	}

	public function setContentData($contentData) {
		$this->contentData = $contentData;
	}

	public function setTimeout($timeout) {
		$this->timeout = $timeout;
	}

	public function setBindAddress($bindAddress) {
		$this->bindAddress = $bindAddress;
	}
}

class HttpProxyClient extends HttpClient {

	public function __construct() {
		global $_SERVER;

		parent::__construct();

		foreach ($_SERVER as $name => $value) {

			if (preg_match('/^http_(.+)$/i', $name, $group)) {
				$headerName = str_replace('_', '-', $group[1]);
				$headerValue = $value;
				$this->headers->add($headerName, $headerValue);
			}
		}
	}

	public function execute() {
		parent::execute();

		$headers = $this->response->getHeaders();

		for ($index = 0; $index < count($headers); $index++) {
			$header = $headers->get($index);
			$name = $header->getName();
			$value = $header->getValue();

			header("$name: $value");
		}
	}
}

?>
