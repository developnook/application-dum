<?php

require_once('com/moserv/sql/connection.php');
require_once('com/moserv/net/session.php');
require_once('com/moserv/util/web.php');

class NewQuitLoader {

	private $sesssion;
	private $telcos;
	private $newQuits;

	public function __construct($session) {
		$this->session = $session;
		$this->telcos = null;
		$this->newQuits = null;
	}

	public function loadTelcos() {
		$connection = $this->session->getConnection();
		$query = $connection->createQuery('
			select
				telco_id,
				telco_name
			from message_service.telco
			where telco_id > 1
			order by telco_id
		');

		$query->open();

		$this->telcos = $query->getResultArray();
	}


	public function loadNewQuits($offset = 0, $limit = 50) {

		if ($this->telcos == null) {
			$this->loadTelcos();
		}

		$userId = $this->session->getVar('userId');
		$connection = $this->session->getConnection();
		$query = $connection->createQuery('
			select
				n.sys_date,
				case when n.sys_date >= date(u.begin_timestamp) then sum(n.new) else 0 end as n,
				case when n.sys_date >= date(u.begin_timestamp) then sum(n.quit) else 0 end as q,
				case when n.sys_date >= date(u.begin_timestamp) then sum(n.new_acc - n.quit_acc) - coalesce(sum(b.new_acc - b.quit_acc), 0) else 0 end as t
			from message_service.newquit_ex n
				join (select date_sub(current_date(), interval series_id - 1 + ? day) as sys_date from message_service.series order by series_id limit ?) d using (sys_date)
				left join b2b.portal_user_service q using (service_id)
				left join b2b.portal_user u using (user_id)
				left join message_service.newquit_ex b on b.service_id = q.service_id and b.telco_id = n.telco_id and b.sys_date = date_sub(date(u.begin_timestamp), interval 1 day)
			where n.telco_id = ?
			and q.user_id = ?
			and q.enabled = 1
			group by n.sys_date
			order by n.sys_date desc
		');

		$hash = array();
		$this->newQuits = array();
		
		foreach ($this->telcos as $telco) {

			$query->setInt(1, $offset);
			$query->setInt(2, $limit);
			$query->setInt(3, $telco['telco_id']);
			$query->setInt(4, $userId);

			$query->open();

			$rows = $query->getResultArray();

			foreach ($rows as $row) {

				if (!array_key_exists($row['sys_date'], $hash)) {
					$this->newQuits[] = array('sys_date' => $row['sys_date']);

					$hash[$row['sys_date']] = &$this->newQuits[count($this->newQuits) - 1];
				}

				$hash[$row['sys_date']]["{$telco['telco_name']}-n"] = $row['n'];
				$hash[$row['sys_date']]["{$telco['telco_name']}-q"] = $row['q'];
				$hash[$row['sys_date']]["{$telco['telco_name']}-t"] = $row['t'];
			}
		}

	}

	public function getTelcos() {
		if ($this->telcos == null) {
			$this->loadTelcos();
		}

		return $this->telcos;
	}


	public function getNewQuits() {
		if ($this->newQuits == null) {
			$this->loadNewQuits();
		}

		return $this->newQuits;
	}
}

class ServiceLoader {

	private $session;

	public function __construct($session) {
		$this->session = $session;
	}

	public function execute() {
		global $_SESSION;

		session_start();
		$connection = $this->session->getConnection();
		$userId = $this->session->getVar('userId');

		$query = $connection->createQuery('
			select
				s.shortcode,
				s.service_id,
				upper(s.keyword) as keyword,
				s.ivr_code,
				u.enabled
			from b2b.portal_user_service u
				join message_service.service s using (service_id)
			where u.user_id = ?
		');

		$query->setInt(1, $userId);

		$query->open();

		$rows = $query->getResultArray();

		$this->shortcodes = array();
		$prev = '';

		foreach ($rows as $row) {
			$shortcode	= $row['shortcode'];
			$serviceId	= $row['service_id'];
			$keyword	= $row['keyword'];
			$ivrCode	= $row['ivr_code'];
			$enabled	= $row['enabled'];


			if (!array_key_exists($shortcode, $this->shortcodes)) {
				$this->shortcodes[$shortcode] = array();
			}

			$this->shortcodes[$shortcode][] = array(
				'service-id'	=> $serviceId,
				'keyword'	=> $keyword,
				'ivr-code'	=> $ivrCode,
				'enabled'	=> $enabled
			);
		}

		return $this->shortcodes;
	}
}

class ServiceSwitcher {

	private $session;
	private $serviceId;
	private $bit;

	public function __construct($session) {
		$this->session = $session;
	}

	public function setServiceId($serviceId) {
		$this->serviceId = $serviceId;
	}

	public function setBit($bit) {
		$this->bit = $bit;
	}

	public function execute() {
		$userId = $this->session->getVar('userId');
		$connection = $this->session->getConnection();
		$query = $connection->createQuery('
			update b2b.portal_user_service
			set enabled = ?
			where user_id = ?
			and service_id = ?
		');

		$query->setInt(1, $this->bit);
		$query->setInt(2, $userId);
		$query->setInt(3, $this->serviceId);

		$query->open();
	}
}

class PasswordChanger {

	public static $statuses = array(
		'The password has been changed successfully.',
		'The given new and verified password do not match!',
		'The given new password is too short!',
		'The current password is not correct!'
	);

	private $session;
	private $userId;
	private $currentPassword;
	private $newPassword;
	private $verifiedPassword;
	private $code;

	public function __construct($session) {
		$this->session = $session;
		$this->userId = $this->session->getVar('userId');
		$this->code = -1;
	}

	public function execute() {
		$this->code = $this->validate();

		$this->saveTransaction($this->code);

		return $this->code;
	}

	protected function checkCurrentPassword() {
		$connection = $this->session->getConnection();
		$query = $connection->createQuery('
			select
				user_id
			from b2b.portal_user
			where user_id = ?
			and password = md5(?)
		');

		$query->setInt(1, $this->userId);
		$query->setString(2, $this->currentPassword);

		$query->open();

		$rows = $query->getResultArray();

		return (count($rows) > 0);
	}


	protected function saveTransaction() {
		$connection = $this->session->getConnection();
		$query = $connection->createQuery('
			insert into b2b.password_change (
				user_id,
				current_password,
				new_password,
				verified_password,
				change_code
			)
			values (
				?,
				?,
				?,
				?,
				?
			)
		');

		$query->setInt(1, $this->userId);
		$query->setString(2, $this->currentPassword);
		$query->setString(3, $this->newPassword);
		$query->setString(4, $this->verifiedPassword);
		$query->setInt(5, $this->code);

		$query->open();
	}

	protected function updatePassword() {
		$connection = $this->session->getConnection();
		$query = $connection->createQuery('
			update b2b.portal_user
			set password = md5(?)
			where user_id = ?
		');

		$query->setString(1, $this->newPassword);
		$query->setInt(2, $this->userId);

		$query->open();
	}

	public function validate() {


		if ($this->newPassword != $this->verifiedPassword) {
			return 1;
		}

		if (strlen($this->newPassword) < 7) {
			return 2;
		}

		if (!$this->checkCurrentPassword()) {
			return 3;
		}

		$this->updatePassword();

		return 0;
	}

	public function setCurrentPassword($currentPassword) {
		$this->currentPassword = $currentPassword;
	}

	public function setNewPassword($newPassword) {
		$this->newPassword = $newPassword;
	}

	public function setVerifiedPassword($verifiedPassword) {
		$this->verifiedPassword = $verifiedPassword;
	}

	public function getCode() {
		return $this->code;
	}

	public function getMessage() {
		return PasswordChanger::$statuses[$this->code];
	}
}

?>
