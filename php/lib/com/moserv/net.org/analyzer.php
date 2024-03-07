<?php

require_once('com/moserv/net/url.php');
require_once('com/moserv/security/rsa.php');
require_once('com/moserv/security/serializer.php');
require_once('com/moserv/sql/connection.php');

abstract class HttpAnalyzer {

	const TEL_UNK	= -1;
	const TEL_MOS	= 1;
	const TEL_AIS	= 2;
	const TEL_DTC	= 3;
	const TEL_TMV	= 4;
	const TEL_TMH	= 5;

	protected $connection;
	protected $msisdn;
	protected $telcoId;

	public function __construct($connection) {
		$this->connection = $connection;

		$this->msisdn = null;
		$this->telcoId = null;
	}

	abstract public function execute();

	public function getMsisdn() {
		return $this->msisdn;
	}

	public function getTelcoId() {
		return $this->telcoId;
	}

	public function getCapsule() {
		$data = array(
			'msisdn'	=> $this->msisdn,
			'telcoId'	=> $this->telcoId
		);

		$capsule = Serializer::encapsulate($data);

		return $capsule;
	}
}

class HeadersAnalyzer extends HttpAnalyzer {

	protected function getMaskTelcoFromHeader() {
		global $_SERVER;

		$mask = array();

		if (!empty($_SERVER['HTTP_X_OPER']) && $_SERVER['HTTP_X_OPER'] == 'TRUEH') {
			$mask[] = HttpAnalyzer::TEL_TMH;
		}
#		elseif (!empty($_SERVER['HTTP_X_WAP_PROFILE']) && $_SERVER['HTTP_X_WAP_PROFILE'] == 'TRUE') {
#			$mask[] = HttpAnalyzer::TEL_DTC;
#			$mask[] = HttpAnalyzer::TEL_TMV;
#		}
		elseif (!empty($_SERVER['HTTP_X_NOKIA_MSISDN'])) {
			$mask[] = HttpAnalyzer::TEL_DTC;
		}
		elseif (!empty($_SERVER['HTTP_X_MSISDN'])) {
			$mask[] = HttpAnalyzer::TEL_AIS;
#			$mask[] = HttpAnalyzer::TEL_TMV;
		}

#		Logger::$logger->info("header mask = " . print_r($mask, true));

		return $mask;
	}

	protected function getMaskTelcoFromAddress() {
		global $_SERVER;

		$remoteAddress = (empty($_SERVER['HTTP_X_FORWARDED_FOR']))? $_SERVER['REMOTE_ADDR']: $_SERVER['HTTP_X_FORWARDED_FOR'];

		$mask = array();

		$query = $this->connection->createQuery('
			select
				telco_id
			from wap.remote_address force index (u_remote_address)
			where inet_aton(?) between begin_address and end_address
			group by telco_id
			order by telco_id
		');

#		$query->setString(1, $this->getRemoteAddress());
		$query->setString(1, $remoteAddress);
		$sql = $query->getParsedSql();
#		Logger::$logger->info("sql = $sql");

		$query->open();

		$rows = $query->getResultArray();

		foreach ($rows as $row) {
			$mask[] = $row['telco_id'];
		}

#		Logger::$logger->info("address mask = " . print_r($mask, true));


		return $mask;
	}

	public function execute($checkAddr = true) {
		global $_SERVER;
		global $_REQUEST;

		if (array_key_exists('_i', $_REQUEST)) {
			return false;
		}

		$this->msisdn = null;
		$this->telcoId = null;

		$headers = array(
			'HTTP_X_MSISDN', # ais, tmv, rmv
			'HTTP_X_NOKIA_MSISDN' # dtc
		);

		foreach ($headers as $header) {
			if (!empty($_SERVER[$header]) && preg_match('/^[0-9]+$/', $_SERVER[$header])) {
				$this->msisdn = $_SERVER[$header];

				break;
			}
		}

#		if (preg_match('/^0([89][0-9]{8})/', $this->msisdn, $group)) {
		if (preg_match('/^0([0-9]{9})$/', $this->msisdn, $group)) {
			list(, $suffix) = $group;
			$this->msisdn = "66{$suffix}";
		}


		$headerMask = $this->getMaskTelcoFromHeader();

		switch (count($headerMask)) {
			case 1:
				$this->telcoId = $headerMask[0];
			break;

			case 0:
				if ($checkAddr) {
					$addressMask = $this->getMaskTelcoFromAddress();
					$this->telcoId = (count($addressMask) == 1)? $addressMask[0]: HttpAnalyzer::TEL_UNK;
				}
			break;

			default:
				if ($checkAddr) {
					$addressMask = $this->getMaskTelcoFromAddress();
					$merge = array_values(array_intersect($headerMask, $addressMask));

					$this->telcoId = (count($merge) == 1)? $merge[0]: HttpAnalyzer::TEL_UNK;
				}
			break;
		}


		return (!empty($this->msisdn) && !empty($this->telcoId));
	}
}


class ParamsAnalyzer extends HttpAnalyzer {

	public function execute() {
		global $_REQUEST;
		global $_SERVER;

		$this->msisdn = null;
		$this->telcoId = null;

		if (array_key_exists('_i', $_REQUEST)) {
			$encap = $_REQUEST['_i'];
			$decap = Serializer::decapsulate($encap);

			$this->msisdn = $decap['msisdn'];
			$this->telcoId = $decap['telcoId'];
		}

		return (!empty($this->msisdn) && !empty($this->telcoId));
	}
}


class GatewayAnalyzer extends HttpAnalyzer {
	protected function getUrl() {
		$targetUrl = new Url();
		$currentUrl = new Url();

		$targetUrl->setHost('m.kangped.com');
		$targetUrl->setPath('/r.exe.php');
		$targetUrl->setParams(null);


		$iarray = array('url' => $currentUrl->toString());
		$icapsule = Serializer::encapsulate($iarray);

		$targetUrl->setParam('_u', $icapsule);


		return $targetUrl;
#		return $url->toString();
	}

#	protected function getUri() {
#		$url = new Url();
#		$port = $url->getPort();
#
#		if (empty($port) || $port == 80)
##			return "http://m.sabver.com/r.exe.php";
#			return "http://m.kodfin.com/r.exe.php";
#		else
##			return "http://m.sabver.com:{$port}/r.exe.php";
#			return "http://m.kodfin.com:{$port}/r.exe.php";
#	}




#	protected function getParams() {
#		global $_SERVER;
#
#		$url = new Url();
#		$config = array('url' => $url->toString());
#		$capsule = Serializer::encapsulate($config);
#		$params = array('_u' => $capsule);
#
#		return $params;
#	}

	public function execute() {
		global $_REQUEST;
		global $_SERVER;

		if ($_SERVER['SERVER_PORT'] == 443) {
			return false;
		}

		if (array_key_exists('_i', $_REQUEST)) {
			$array = Serializer::decapsulate($_REQUEST['_i']);

			return (
				$array['msisdn'] != null &&
				$array['telcoId'] != null &&
				$array['telcoId'] != HttpAnalyzer::TEL_UNK
			);
		}
		else {
#			$uri = $this->getUri();
			$url = $this->getUrl();
#			$querystring = http_build_query($this->getParams());

#			$url = new Url("{$uri}?{$querystring}");
			$url->redirect();

			return true;
		}
	}
}

class HitInfoAnalyzer extends HttpAnalyzer {

	protected $analyzer;

	public function __construct($connection) {
		parent::__construct($connection);

		$this->analyzer = null;
	}

	public function execute() {
		if (($analyzer = new ParamsAnalyzer($this->connection)) && $analyzer->execute()) {
			$this->analyzer = $analyzer;

			return true;
		}

		if (($analyzer = new HeadersAnalyzer($this->connection)) && $analyzer->execute()) {
			$this->analyzer = $analyzer;

			return true;
		}

		if (($analyzer = new GatewayAnalyzer($this->connection)) && $analyzer->execute()) {
			return true;
		}

		return false;
	}

	public function getMsisdn() {
		return ($this->analyzer == null)? '': $this->analyzer->getMsisdn();
	}

	public function getTelcoId() {
		return ($this->analyzer == null)? HttpAnalyzer::TEL_UNK: $this->analyzer->getTelcoId();
	}
}


?>
