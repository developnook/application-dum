<?php

require_once('com/moserv/mq/mq.php');
require_once('com/moserv/net/url.php');
require_once('com/moserv/sql/connection.php');
require_once('com/moserv/sweat16/mailer.php');

// hello test mr.php

class Session {


	private $persistent;
	private $transaction;


	private $url;
	private $connection;
	private $mq;
	private $sessionId;
	private $hitId;
	private $mailer;

	public static $instance = null;

	public static function newInstance() {
		if (Session::$instance == null) {
			Session::$instance = new Session();
			Session::$instance->init();
		}

		return Session::$instance;
	}

	public function __construct() {
		$this->persistent = true;
		$this->transaction = true;
		$this->url = new Url();


		$this->mq = new RabbitMQ(
			'bangkok.moserv.mobi',
			5672,
			'sweat16',
			'supermario',
			'sweat16'
		);

		$this->mq->pconnect();

		$database = new MySqlEx(
			'sweat16',			# base
			'phatthalung.moserv.mobi',	# host
			'3306',				# port
			'sweat16_user',			# user
			'supermariO16!'			# pass
		);

		$this->connection = new Connection($database, $this->persistent);

		$this->mailer = new Mailer($this);

		if ($this->transaction) {
			$this->connection->beginTransaction();
		}
	}


	public function __destruct() {
		if ($this->transaction) {
			$this->connection->commit();
		}
	}

	protected function startSession() {
		$host = $this->url->getHost();
		$domain = preg_replace('/^[^\\.]+/', '', $host);


		session_set_cookie_params(
			0,			# lifetime
			'/',			# path
##			$this->url->getHost(),	# domain
			$domain,		# domain
			true,			# https only
			false			# http only
		);

		session_name('sweat16-session-id');
		session_start();
	}

	public function init() {


		$this->startSession();

		if (($this->sessionId = $this->querySession()) == 0) {
			$this->sessionId = $this->newSession();
		}

		$this->hitId = $this->newHit($this->sessionId);
	}

	public function getRemoteAddress() {
		global $_SERVER;
		$remoteAddress = null;

		if (empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$remoteAddress = $_SERVER['REMOTE_ADDR'];
		}
		else {
			$remoteAddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
		}

		return $remoteAddress;
	}

	public function getUserAgent() {
		global $_SERVER;
		$userAgent = $_SERVER['HTTP_USER_AGENT'];

		return $userAgent;
	}

	public function getMd5Sum() {
		global $_SERVER;

		$userAgent = $this->getUserAgent();
		$remoteAddress = $this->getRemoteAddress();

		$md5sum = md5("{$userAgent}{$remoteAddress}");

		return $md5sum;
	}

	public function getSessionLine() {
		$sessionLine = session_id();

		return $sessionLine;
	}

	public function getUrl() {
		return $this->url;
	}

	public function getReferer() {
		global $_SERVER;

		$referer = (empty($_SERVER['HTTP_REFERER']))? '': $_SERVER['HTTP_REFERER'];

		return $referer;
	}

	protected function querySession() {
		$query = $this->connection->createQuery(
<<<sql
			select
				session_id
			from sweat16.session
			where session_line = ?
			and md5sum = ?
sql
		);

		$query->setString(1, $this->getSessionLine());
		$query->setString(2, $this->getMd5Sum());


		$query->open();

		$rows = $query->getResultArray();

		$sessionId = (count($rows) == 0)? 0: $rows[0]['session_id'];

		return $sessionId;
	}



	protected function newSession() {
		$query = $this->connection->createQuery(
<<<sql
			insert into sweat16.session (
				session_line,
				md5sum,
				user_agent,
				remote_address
			)
			values (
				?,
				?,
				?,
				?
			)
sql
		);

		$query->setString(1, $this->getSessionLine());
		$query->setString(2, $this->getMd5Sum());
		$query->setString(3, $this->getUserAgent());
		$query->setString(4, $this->getRemoteAddress());

		$query->open();
		$sessionId = $this->connection->lastId();

		return $sessionId;
	}

	protected function newHit($sessionId) {
		$query = $this->connection->createQuery(
<<<sql
			insert into sweat16.hit (
				session_id,
				url,
				referer
			)
			values (
				?,
				?,
				?
			)
sql
		);

		$query->setInt(1, $sessionId);
		$query->setString(2, $this->getUrl()->toString());
		$query->setString(3, $this->getReferer());

		$query->open();
		$hitId = $this->connection->lastId();

		return $hitId;
	}

	public function getConnection() {
		return $this->connection;
	}

	public function getMq() {
		return $this->mq;
	}

	public function getMailer() {
		return $this->mailer;
	}

	public function getSessionId() {
		return $this->sessionId;
	}

	public function getHitId() {
		return $this->hitId;
	}

	public function setVar($key, $value) {
		$_SESSION[$key] = $value;
	}

	public function getVar($key) {
		if (empty($_SESSION[$key])) {
			return null;
		}
		else {
			return $_SESSION[$key];
		}
	}

	public function removeVar($key) {
		unset($_SESSION[$key]);
	}

	public function isSignedOn() {
		$userId = $this->getVar('user-id');

		return (($userId != null) && ($userId != -1))? true: false;
	}
}
