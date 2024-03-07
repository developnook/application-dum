<?php

require_once('com/moserv/sql/connection.php');
require_once('com/moserv/net/session.php');
require_once('com/moserv/util/web.php');
require_once('com/moserv/log/logger.php');

class Authenticator {

	const proj_b2b		= 0x01;
	const proj_gumnan	= 0x02;
	const proj_console	= 0x03;

	const auth_succ		= 0x00;
	const auth_wusr		= 0x01;
	const auth_wpas		= 0x02;
	const auth_wfet		= 0x03;

	const port_b2b		= 0x01;
	const port_gumnan	= 0x02;
	const port_console	= 0x03;

	private $session;
	private $username;
	private $password;
	private $userId;
	private $projId;
	private $portalId;
	private $masterPassword;

	public function __construct($session) {
		$this->session = $session;
		$this->userId = -1;
		$this->projId = -1;
		$this->masterPassword = 'castlevania';
	}

	public function setProjId($projId) {
		$this->projId = $projId;
	}

	public function setProject($projId) {
		$this->projId = $projId;
	}

	public function setPortal($portalId) {
		$this->portalId = $portalId;
	}

	public function setUsername($username) {
		$this->username = $username;
	}

	public function setPassword($password) {
		$this->password = $password;
	}

	public function setMasterPassword($masterPassword) {
		$this->masterPassword = $masterPassword;
	}

	protected function queryUsername() {
		$connection = $this->session->getConnection();
		$query = $connection->createQuery('
			select
				count(*) as counter
			from access_control.users
			where username = lower(?)
		');

		$query->setString(1, $this->username);
		$query->open();

		$rows = $query->getResultArray();

		return (count($rows) > 0 && $rows[0]['counter'] > 0);
	}

	protected function queryPassword() {
		$connection = $this->session->getConnection();
		$query = $connection->createQuery('
			select
				user_id,
				date(begin_timestamp) as begin_date,
				username
			from access_control.users
			where username = lower(?)
			and (password = md5(?) or ? = ?)
		');

		$query->setString(1, $this->username);
		$query->setString(2, $this->password);
		$query->setString(3, $this->password);
		$query->setString(4, $this->masterPassword);
		$query->open();

		$rows = $query->getResultArray();

		if (count($rows) > 0) {
			$this->userId = $rows[0]['user_id'];
#			Logger::$logger->info("userId = {$this->userId}");

			return true;
		}
		else
			return false;
	}

	protected function queryFeature() {

		$connection = $this->session->getConnection();
		$query = $connection->createQuery('
			select
				pf.feat_id
			from access_control.portal_feature pf
				join access_control.group_feature gf using (feat_id)
				join access_control.user_group ug using (group_id)
			where pf.portal_id = ?
			and ug.user_id = ?
		');

		$query->setInt(1, $this->portalId);
		$query->setInt(2, $this->userId);

#		echo $query->getParsedSql();
#		exit;

		$query->open();

		$rows = $query->getResultArray();

		return (count($rows) > 0);
	}

	protected function saveAuthen($authenCode) {
		$connection = $this->session->getConnection();
		$query = $connection->createQuery('
			insert into access_control.authen (
				try_username,
				try_password,
				authen_code,
				portal_id,
				user_id,
				session_id
			)
			values (
				?,
				?,
				?,
				?,
				?,
				?
			)
		');

		$query->setString(1, $this->username);
		$query->setString(2, $this->password);
		$query->setInt(3, $authenCode);
		$query->setInt(4, $this->portalId);
		$query->setInt(5, $this->userId);
		$query->setInt(6, $this->session->getSessionId());
		$query->open();


	}

	public function execute() {
		if (!$this->queryUsername()) {
			$this->saveAuthen(Authenticator::auth_wusr);

			return Authenticator::auth_wusr;
		}

		if (!$this->queryPassword()) {
			$this->saveAuthen(Authenticator::auth_wpas);

			return Authenticator::auth_wpas;
		}

		if (!$this->queryFeature()) {
			$this->saveAuthen(Authenticator::auth_wfet);

			return Authenticator::auth_wfet;
		}


		$this->session->setVar('userId', $this->userId);
//		$this->session->setVar('beginDate', $this->rows[0]['begin_date']);
		$this->session->setVar('beginDate',date("Y-m-d H:i:s"));
#		echo $this->username;
#		exit;
		$this->session->setVar('username', $this->username);

		$this->saveAuthen(Authenticator::auth_succ);

		return Authenticator::auth_succ;
	}

	public function getUserId() {
		return $this->userId;
	}
}

class FeatureLoader {

	private $session;
	private $userId;
	private $portalId;
	private $rows;

	public function __construct($session) {
		$this->session = $session;
		$this->userId = -1;
		$this->portalId = -1;
	}

	public function setPortalId($portalId) {
		$this->portalId = $portalId;
	}

	public function setPortal($portalId) {
		$this->portalId = $portalId;
	}

	public function setUserId($userId) {
		$this->userId = $userId;
	}

	public function load() {
                $connection = $this->session->getConnection();
                $query = $connection->createQuery('
			select
				f.feat_id as id,
				f.feat_name as name,
				f.path,
				group_id
			from access_control.feature f
				join (
					select
						pf.feat_id,ug.group_id
					from access_control.user_group ug
						join access_control.group_feature gf using (group_id)
						join access_control.portal_feature pf using (feat_id)
					where ug.user_id = ?
					and pf.portal_id = ?
					group by pf.feat_id		
				) x using (feat_id)
			order by f.rank
		');

		$query->setInt(1, $this->userId);
		$query->setInt(2, $this->portalId);

		$query->open();

		$this->rows = $query->getResultArray();
	}

	public function getRows() {
		return $this->rows;
	}
}

class PasswordChanger {

	const pwd_unkn = -1;
	const pwd_succ = 0;
	const pwd_blnk = 1;
	const pwd_nmat = 2;
	const pwd_wrng = 3;

	public static $messages = array(
		'ระบบได้ทำการเปลี่ยนรหัสเรียบร้อยแล้ว',
		'ระบบไม่อนุญาติให้ใช้ค่าว่างเป็นรหัสผ่าน',
		'รหัสผ่านใหม่ไม่ตรงกับยืนยันรหัสผ่านใหม่',
		'รหัสผ่านปัจจุบันไม่ตรงกับในระบบ'
	);

	private $code;
	private $session;
	private $curPassword;
	private $newPassword;
	private $verPassword;

	public function __construct($session) {
		$this->code = self::pwd_unkn;
		$this->session = $session;
	}

	public function setCurPassword($curPassword) {
		$this->curPassword = $curPassword;
	}

	public function setNewPassword($newPassword) {
		$this->newPassword = $newPassword;
	}

	public function setVerPassword($verPassword) {
		$this->verPassword = $verPassword;
	}

	public function execute() {

		if (($this->code = $this->validate()) == self::pwd_succ) {
			$this->code = $this->change();
		}


		$this->log();

		return $this->code;
	}


	protected function authen() {
                $connection = $this->session->getConnection();
		$userId = $this->session->getVar('userId');

		$query = $connection->createQuery('
			select
				username
			from access_control.users
			where user_id = ?
			and password = md5(?)
		');

		$query->setInt(1, $userId);
		$query->setString(2, $this->curPassword);

		$query->open();


		$rows = $query->getResultArray();

		return (count($rows) > 0);
	}

	protected function validate() {
		if ($this->curPassword == '') {
			return self::pwd_blnk;
		}

		if ($this->newPassword != $this->verPassword) {
			return self::pwd_nmat;
		}
		
		if (!$this->authen()) {
			return self::pwd_wrng;
		}

		return self::pwd_succ;
	}

	protected function change() {
                $connection = $this->session->getConnection();
		$userId = $this->session->getVar('userId');

		$query = $connection->createQuery('
			update access_control.users
				set password = md5(?)
			where user_id = ?
		');

		$query->setString(1, $this->newPassword);
		$query->setInt(2, $userId);

		$query->open();

		return self::pwd_succ;
	}

	protected function log() {
		# tet
	}

	public function getCode() {
		return $this->code;
	}

	public function getMessage() {
		return self::$messages[$this->code];
	}
}

?>
