<?php

require_once('com/moserv/cache/cache.php');
require_once('com/moserv/log/logger.php');
require_once('com/moserv/mq/mq.php');
require_once('com/moserv/net/analyzer.php');
require_once('com/moserv/net/url.php');
require_once('com/moserv/sql/connection.php');
require_once('com/moserv/util/web.php');
#require_once('com/scientiamobile/tera-wurfl/TeraWurfl.php');

class Session {

	public static $session = null;

	protected $sessionLine;
	protected $namespace;

	protected function startSession() {
		session_start();
	}

	public function getSessionLine() {
		if ($this->sessionLine == null) {
			$this->sessionLine = session_id();
		}

		return $this->sessionLine;
	}

	public function getNamespace() {
		if ($this->namespace == null) {
			
			$url = new Url();
			$host = $url->getHost();

			$tokens	= explode('.', $host);
			$rtokens = array_reverse($tokens);
			$this->namespace = implode('.', $rtokens);
		}

		return $this->namespace;
	}

	public function getCfgVar($varName) {
		$default = 'th.co.moserv.m';
		$namespace = $this->getNamespace();


		if (($value = get_cfg_var("{$namespace}.{$varName}")) === FALSE && ($value = get_cfg_var("{$default}.{$varName}")) === FALSE) {
			$value = null;
		}

#		Logger::$logger->info("namespace = $namespace, varname = $varName, value = $value");

		return $value;
	}

	public function getPortalName() {
		$url = new Url();
		$host = $url->getHost();

		if (preg_match('/^([a-z0-9_-]+)\.([a-z0-9_-]+)\.(com|mobi|co\.th)$/i', $host, $groups)) {
			list($sub, $name, $tail) = $groups;
		}
		else
			$name = 'moserv';

		return $name;
	}

	public function setVar($varname, $value) {
		global $_SESSION;

		if (!isset($_SESSION)) {
			$this->startSession();
		}

		$_SESSION[$varname] = $value;
	}

	public function getVar($varname) {
		global $_SESSION;

		if (!isset($_SESSION)) {
			$this->startSession();
		}

		return (session_id() && $_SESSION && array_key_exists($varname, $_SESSION))? $_SESSION[$varname]: null;
	}

	public function getRemoteAddress() {
		global $_SERVER;

		if ($this->remoteAddress == null)
			$this->remoteAddress = (empty($_SERVER['HTTP_X_FORWARDED_FOR']))? $_SERVER['REMOTE_ADDR']: $_SERVER['HTTP_X_FORWARDED_FOR'];

		return $this->remoteAddress;
	}

	public function getUserAgent() {
		global $_SERVER;

		if ($this->userAgent == null)
			$this->userAgent = $_SERVER['HTTP_USER_AGENT'];

		return $this->userAgent;
	}


	public function isIPhone() {
		return stripos($this->getUserAgent(), 'iPhone');
	}

	public function isAndroid() {
		return stripos($this->getUserAgent(), 'Android');
	}

	public function getUrlReferral() {
		global $_SERVER;

		if ($this->urlReferral == null) {

			$this->urlReferral = (empty($_SERVER['HTTP_REFERER']))? '': $_SERVER['HTTP_REFERER'];
		}

		return $this->urlReferral;
	}

	public static function getPhpVersion() {
		$version = array();
		list($version['major'], $version['minor'], $version['release']) = explode('.', phpversion());

		return $version;
	}
}


class WebSession extends Session {


	public function __construct() {
		$this->startSession();
	}

	public function getUserId() {
		global $_SESSION;

		return (empty($_SESSION['user_id']))? null: $_SESSION['user_id'];
	}

	public function setUserId($userId) {
		global $_SESSION;

		$_SESSION['user_id'] = $userId;
	}

	public function clear() {
		session_unset();

		session_destroy();
	}

	public static function create() {
		if (self::$session == null)
			self::$session = new WebSession();

		return self::$session;
	}

	public static function createDbConnection($persistent = false) {
		$base = Web::getCfgVar('base');
		$host = Web::getCfgVar('host');
		$port = Web::getCfgVar('port');
		$user = Web::getCfgVar('user');
		$pass = Web::getCfgVar('pass');

#		$database = new MySql($base, $host, $port, $user, $pass);
		$database = new MySqlEx($base, $host, $port, $user, $pass);
		$connection = new Connection($database, $persistent);

		return $connection;
	}
}


class WapSession extends Session {


	protected $connecton;
	protected $cache;
#	protected $wurfl;

	protected $telcoId;
	protected $sessionId;
	protected $sessionLine;
	protected $remoteAddress;
	protected $userAgent;
	protected $urlReferral;

	protected $freshRedirect;
	protected $hitId;
	protected $transaction;
	protected $persistent;
	protected $mq;

	public function __construct($freshRedirect = true) {
		self::$session = $this;

#		$line = sprintf("%s [%d] - %s\n", date("Y-m-d h:i:s"), getmypid(), Web::curPageUrl());
#		file_put_contents("/usr/project/temp.log", $line, FILE_APPEND);

		$this->freshRedirect = $freshRedirect;

		$this->namespace = null;

		$this->telcoId = HttpAnalyzer::TEL_UNK;
		$this->msisdn = null;
		$this->sessionId = null;
		$this->sessionLine = null;
		$this->remoteAddress = null;
		$this->userAgent = null;
		$this->urlReferral = null;
		$this->hitId = 0;

		$this->transaction = false;
		$this->persistent = true;

		$this->cache = WapSession::createCache();
		$this->connection = WapSession::createDbConnection($this->persistent);
		$this->connection->setCache($this->cache);

//		$this->mq = WapSession::createMQ();

		if ($this->transaction) {
			$this->connection->beginTransaction();
		}
	}

#	public function hitLog($line) {
#		$text = sprintf("%s [%d:%s:%s] - %s\n", date("Y-m-d h:i:s"), getmypid(), $line);
#		file_put_contents("/usr/project/temp.log", $text, FILE_APPEND);
#	}

	public function takeLog($line) {
		global $_SERVER;

		$mt = microtime(true);
		$msec = floor(($mt - floor($mt)) * 1000);
		$port = (preg_match('/http_([0-9]+)/', $_SERVER['DOCUMENT_ROOT'], $captures))? $captures[1]: '0';
		$url = new Url();

#		$server = (preg_match('/^[a-zA-Z0-9_-]+/', gethostname(), $group))? $group[0]: 'unknown';
		$server = (preg_match('/^[a-zA-Z0-9_-]+/', $url->getHost(), $group))? $group[0]: 'unknown';

		$text = sprintf(
			"%s.%03d [%s:%d:%06d] - %s\n",
				date("Y-m-d H:i:s"),
				$msec,
				$server,
				$port,
				getmypid(),
				$line
		);

##		if ($this->telcoId != 3) {
			file_put_contents("/usr/project/temp.log", $text, FILE_APPEND);
##		}
	}

	public static function createDbConnection($persistent = false) {
		$base = Web::getCfgVar('base');
		$host = Web::getCfgVar('host');
		$port = Web::getCfgVar('port');
		$user = Web::getCfgVar('user');
		$pass = Web::getCfgVar('pass');

#		$database = new MySql($base, $host, $port, $user, $pass);


		$version = Session::getPhpVersion();

		if ($version['major'] <= 5)
			$database = new MySql($base, $host, $port, $user, $pass);
		else
			$database = new MySqlEx($base, $host, $port, $user, $pass);

		$connection = new Connection($database, $persistent);

##		if (($memCachedHosts = Web::getCfgVar('memcached_hosts')) != null) {
##			$cacheHosts = explode(':', $memCachedHosts);
##
##			foreach ($cacheHosts as $host) {
##				$connection->addCacheServer($host);
##			}
##		}

		return $connection;
	}

	public static function createCache() {
		$cache = Cache::create('memcache');
		$cache->pconnect('maehongson.moserv.mobi', 11211);

		return $cache;
	}

	public static function createMQ() {
		$mq = new RabbitMQ(
			'bangkok2.moserv.mobi',
			5672,
			'moserv',
			'errakerror',
			'/'
		);

		$mq->pconnect();
//		$mq->connect();

		return $mq;
	}

	public static function freeMQ($mq) {
//		$mq->getConnection()->pdisconnect();
//		$mq->getConnection()->disconnect();
	}

	public function __destruct() {
		if ($this->transaction) {
			$this->connection->commit();
		}

//		WapSession::freeMQ($this->mq);
	}


	protected function startSession() {
		$url = new Url();
		$host = strtolower($url->getHost());

		session_set_cookie_params(
			0,			# lifetime
			'/',			# path
			$host,			# domain
			false,			# https only
			true			# http only
		);

		list($head, $siteName, $tail) = explode('.', strtolower($host));
		$sessionName = "{$siteName}-session-id";

		session_name($sessionName);

		parent::startSession();
	}

	public function start($analyze = true, $save = true, $freshRed = true) {

		$this->startSession();

		if ($analyze) {
			$this->analyzeHitInfo();
		}

		$this->saveSession();

		if ($save) {
			$this->saveHit(($this->isFresh())? 1: 0);
		}


		if ($freshRed) {
			if ($this->freshRedirect)
				$this->freshRedirect();
		}


#		$this->wurfl = new TeraWurfl();
#		$this->wurfl->GetDeviceCapabilitiesFromAgent(); //optionally pass the UA and HTTP_ACCEPT here
		$this->testBlock();
	}

	public function load() {
		$this->loadSessionInfo();
		$this->saveHit(-1);

		$this->testBlock();
	}

	protected function testBlock() {
#		if ($this->telcoId == HttpAnalyzer::TEL_TMV || $this->telcoId == HttpAnalyzer::TEL_TMH) {
#			$url = new Url();
#			header('HTTP/1.0 404 Not Found');
#			header('Status: 404 Not Found');
#			header('Content-Type: text/html');
#
#			echo "<html><head><title>404 Not Found</title></head><body><h1>Not Found</h1><p>The requested URL ".$url->toString()." was not found on this server.</p></body></html>";
#
#			exit;
#		}
	}

	protected function loadSessionInfo() {
		global $_REQUEST;

		$this->sessionId = $_REQUEST['_s'];

		$query = $this->connection->createQuery(
<<<sql
			select
				*
			from wap.session
			where session_id = ?
sql
		);
		$query->setInt(1, $this->sessionId);
		$query->open();

		$result = $query->getResultArray();

		if (count($result) > 0) {
			$this->telcoId = $result[0]['telco_id'];
			$this->msisdn = $result[0]['msisdn'];
			$this->sessionLine = $result[0]['session_line'];
			$this->remoteAddress = $result[0]['remote_address'];
			$this->userAgent = $result[0]['user_agent'];
			$this->urlReferrer = $result[0]['url_referrer'];

			return true;
		}

		return false;
	}



	public function getSessionId() {
		return $this->sessionId;
	}

	public static function create() {
		if (self::$session == null)
			self::$session = new WapSession();

		return self::$session;
	}


	protected function analyzeHitInfo() {
		$analyzer = new HitInfoAnalyzer($this->connection);
		$analyzer->execute();

		$this->telcoId = $analyzer->getTelcoId();
		$this->msisdn = $analyzer->getMsisdn();
	}

	protected function saveSession() {
		$query = $this->connection->createQuery(
<<<sql
			insert into wap.session (session_line, telco_id, msisdn, remote_address, user_agent, url_referrer)
				select
					? as session_line,
					? as telco_id,
					? as msisdn,
					? as remote_address,
					? as user_agent,
					? as url_referal
				from dual
				where not exists (
					select
						*
					from session force index (u_session)
					where sys_timestamp between ? and ?
					and session_line = ?
					and telco_id = ?
					and msisdn = ?
				)
sql
		);

		$query->setString(1, $this->getSessionLine());
		$query->setInt(2, $this->telcoId);
		$query->setString(3, $this->msisdn);
		$query->setString(4, $this->getRemoteAddress());
		$query->setString(5, $this->getUserAgent());
		$query->setString(6, $this->getUrlReferral());

		$query->setString(7, date("Y-m-d H:i:s", strtotime("-3 days")));
		$query->setString(8, date("Y-m-d H:i:s", strtotime("+1 hours")));

		$query->setString(9, $this->getSessionLine());
		$query->setInt(10, $this->telcoId);
		$query->setString(11, $this->msisdn);

//		if ($_REQUEST['p'] == 20) {
//			echo $query->getParsedSql();
//			exit;
//		}

		$query->open();


		$query = $this->connection->createQuery(
<<<sql
			select
				session_id
			from session
			where sys_timestamp between ? and ?
			and session_line = ?
			and telco_id = ?
			and msisdn = ?
sql
		);

		$query->setString(1, date("Y-m-d H:i:s", strtotime("-3 days")));
		$query->setString(2, date("Y-m-d H:i:s", strtotime("+1 hours")));


		$query->setString(3, $this->getSessionLine());
		$query->setInt(4, $this->telcoId);
		$query->setString(5, $this->msisdn);

		$query->open();

		$result = $query->getResultArray();

		if (count($result) > 0) {
			$this->sessionId = $result[0]['session_id'];
		}
	}


	public function isFresh() {
		global $_REQUEST;

		return (array_key_exists('_', $_REQUEST))? false: true;
	}


	public function getUrl() {
		global $_REQUEST;

		if (array_key_exists('code', $_REQUEST)) {
			$url = $_REQUEST['code'];
			$url = str_replace('.xml', '', $url);
		}
		else {
			$url = Web::curPageUrl();
		}

		return $url;
	}

	protected function saveHit($fresh) {
		global $_REQUEST;

#		$this->connection->beginTransaction();

#		Logger::$logger->info("fresh = $fresh");
		$query = $this->connection->createQuery(
<<<sql
			insert into wap.hit (session_id, page_id, url, referrer, fresh)
				select
					session_id,
					? as page_id,
					? as url,
					? as referrer,
					? as fresh
				from session
				where sys_timestamp between ? and ?
				and session_line = ?
				and telco_id = ?
				and msisdn = ?
sql
		);

		Logger::$logger->info("msisdn = {$this->msisdn}, url = {$this->getUrl()}, sessionline = {$this->getSessionLine()}");

		$query->setInt(1, (empty($_REQUEST['p']) || !preg_match('/^[0-9]+$/', $_REQUEST['p']))? -1: $_REQUEST['p']);
		$query->setString(2, $this->getUrl());
		$query->setString(3, $this->getUrlReferral());
		$query->setInt(4, $fresh);



		$query->setString(5, date("Y-m-d H:i:s", strtotime("-1 days")));
		$query->setString(6, date("Y-m-d H:i:s", strtotime("+1 hours")));
		$query->setString(7, $this->getSessionLine());
		$query->setInt(8, $this->telcoId);
		$query->setString(9, $this->msisdn);

//		if ($_REQUEST['p'] == 20) {
//			echo $query->getParsedSql();
//			exit;
//		}

		$query->open();

		$this->hitId = $this->connection->lastId();

#		if ($this->hitId != 0) {
#
#			$query = $this->connection->createQuery('insert into wap.hit_param (hit_id, param_name, param_value) values (?, ?, ?)');
#
#			foreach ($_REQUEST as $key => $value) {
#				$query->setInt(1, $this->hitId);
#				$query->setString(2, $key);
#				$query->setString(3, $value);
#
#				$query->open();
#			}
#		}
	}

	protected function freshRedirect() {
#		if ($this->isFresh()) {
#			$url = $this->getUrl();
#			$rurl = $url . ((strrpos($url, '?') !== false)? '&': '?') . '_=1';
#
#			header("location: $rurl");
#
#			exit;
#		}

		if ($this->isFresh()) {
			$url = new Url($this->getUrl());
			$url->setParam('_', 1);

			$url->redirect();
			exit;
		}
	}

	public function getConnection() {
		return $this->connection;
	}

	public function getCache() {
		return $this->cache;
	}

	public function getMQ() {
		return $this->mq;
	}

	public function getHitId() {
		return $this->hitId;
	}


	public function getTelcoId() {
		return $this->telcoId;
	}

	public function getMsisdn() {
		return $this->msisdn;
	}

	public function getCpId() {
		$telcoId = $this->getTelcoId();

		if ($telcoId == -1) {
			return null;
		}
		else {
			$cpIdLine = $this->getCfgVar('cpid');
			$cpIds = explode(':', $cpIdLine);

			return ( !array_key_exists($telcoId, $cpIds) || empty($cpIds[$telcoId]) )? $cpIds[0]: $cpIds[$telcoId];
		}
	}
}

