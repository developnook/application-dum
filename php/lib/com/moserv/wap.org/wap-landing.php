<?php

require_once('com/moserv/net/redirector.php');
require_once('com/moserv/net/session.php');
require_once('com/moserv/net/url.php');
require_once('com/moserv/sql/connection.php');

class WapLanding {

	private $session;
	private $timestamp;
	private $landingId;

	private $configIndex;
	
	private $url;

	private $landingConfigId;

	public function __construct($session) {
		$this->session = $session;
	}

	public function register($clickId = 0) {

		$this->configIndex = -1;
		$connection = $this->session->getConnection();

		$this->timestamp = new DateTime();

                $query = $connection->createQuery(
<<<sql
                        insert into wap.landing (
				sys_timestamp,
				click_id
			)
			values (
				?,
				?
			)
sql
                );

		$query->setString(1, $this->timestamp->format('Y-m-d H:i:s'));
		$query->setInt(2, $clickId);

              	$query->open();

		$this->landingId = $connection->lastId();

	}

	protected function encrypt($string) {
		return strrev($string);
	}

	protected function decrypt($string) {
		return strrev($string);
	}

#	protected function convertStr($path) {
#		$convStr = base_convert($path, 10,16);
#		return $convStr;
#	}

#	protected function regPath($path) {
#		
#		$pattern = '';
#		$match = preg_match($pattern, $path);
#
#		if ($match) {
#			$result = $this->decrypt($path);
#			return $result;
#		}
#
#		else {
#			return $path;
#		}
#	}

	public function getCode() {

		$code = sprintf('%s%010d', $this->timestamp->format('YmdHis'), $this->landingId);
		
		return $code;
	}

	public function addConfig($url, $method = Redirector::rd_href) {
		$connection = $this->session->getConnection();

		$query = $connection->createQuery(
<<<sql
			insert into wap.landing_config (
				landing_id,
				sys_timestamp,
				time,
				method,
				url
			)
			values (
				?,
				?,
				?,
				?,
				?
			)
sql
		);

		$query->setInt(1, $this->landingId);
		$query->setString(2, $this->timestamp->format('Y-m-d H:i:s'));
		$query->setInt(3, $this->configIndex++);
		$query->setInt(4, $method);
		$query->setString(5, $url);

		$query->open();

	}

	private function parseCode($code) {
		if (preg_match('/^([0-9]{4})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{10})$/', $code, $group)) {
			list(, $yyyy, $mm, $dd, $hh, $ii, $ss, $this->landingId) = $group;

			$this->timestamp = new DateTime("$yyyy-$mm-$dd $hh:$ii:$ss");
		}
	}

	public function hit($code) {

		global $_SERVER;

		$this->parseCode($code);

		$connection = $this->session->getConnection();

		$query = $connection->createQuery(
<<<sql
			select
				lc.method,
				lc.url,
				lc.landing_config_id
			from wap.landing l
				join wap.landing_config lc using (landing_id, sys_timestamp)
			where l.landing_id = ?
			and l.sys_timestamp = ?
			and (l.counter = lc.time or lc.time = -1)
			order by lc.time desc
			limit 1
sql
		);

		$query->setInt(1, $this->landingId);
		$query->setString(2, $this->timestamp->format('Y-m-d H:i:s'));

		$query->open(false);

		$rows = $query->getResultArray();


#		print_r($rows);
#		exit;
		
		if (count($rows) > 0) {
#			$index = $this->getIndex($rows);
			$row = $rows[0];
			
			$this->landingConfigId = $row['landing_config_id'];
#			$url = new Url($row['url']);

#			if ($code == '201802221509030075099314') $url->setParam('__s', $code);

			$redirector = new Redirector();
			$redirector->setMethod($row['method']);
			$redirector->setUrl($row['url']);
#			$redirector->setUrl($url->toString());

			$this->save();

			if (array_key_exists('HTTP_X_NOKIA_MSISDN', $_SERVER)) {
				$redirector->setHeader('X_NOKIA_MSISDN', $_SERVER['HTTP_X_NOKIA_MSISDN']);
			}
			elseif (array_key_exists('HTTP_X_MSISDN', $_SERVER)) {
				$redirector->setHeader('X_MSISDN', $_SERVER['HTTP_X_MSISDN']);
			}

			$redirector->execute();

		}

	}

	private function save() {
		$date = (new DateTime())->format('Y-m-d');
		$connection = $this->session->getConnection();

		$query = $connection->createQuery(
<<<sql
			insert into wap.landing_info (
				landing_config_id,
				sys_timestamp,
				session_id
			)
			values (
				?,
				?,
				?
			)
sql
		);
		
		$query->setInt(1, $this->landingConfigId);
		$query->setString(2, $this->timestamp->format('Y-m-d H:i:s'));
		$query->setInt(3, $this->session->getSessionId());
		$query->open();

		$query = $connection->createQuery(
<<<sql
			update wap.landing
				set counter = counter + 1
			where landing_id = ?
			and sys_timestamp between ? and ?
sql
		);
		$query->setInt(1, $this->landingId);
		$query->setString(2, "{$date} 00:00:00");
		$query->setString(3, "{$date} 23:59:59");
		$query->open();
	}

	public function getUrl() {
		$code = $this->getCode();
		$path = "/land-$code";

		$url = new Url();
		$url->setPath($path);

		return $url->toString();
	}

	public function gotoLanding() {
		$url = new Url($this->getUrl());
		$url->redirect();
	}
}

