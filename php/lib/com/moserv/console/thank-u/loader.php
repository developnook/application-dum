<?php

require_once('com/moserv/sql/connection.php');
require_once('com/moserv/net/session.php');
require_once('com/moserv/util/web.php');
require_once('com/moserv/log/logger.php');

class RestaurantLoader {

	const worksheet_feat_id = 8;
	const worksheet_perm_id = 1;

	const viewall_unknown	= 0x00;
	const viewall_no_feat	= 0x01;
	const viewall_no_perm	= 0x02;
	const viewall_both	= 0x03;

	private $session;
	private $rows;

	public function __construct($session) {
		$this->session = $session;
		
	}

	public function getFeaturePermission(&$groupFeatId, &$groupPermId) {
		$connection = $this->session->getConnection();
		$userId = $this->session->getVar('userId');

		$query = $connection->createQuery('
			select
				gf.group_feat_id,
				gp.group_perm_id
			from access_control.user_group ug
				left join access_control.group_feature gf using (group_id)
				left join access_control.group_permission gp on gf.group_feat_id = gp.group_feat_id and gp.perm_id = ?
			where ug.user_id = ?
			and gf.feat_id = ?
			order by gp.group_perm_id desc
			limit 1
		');

		$query->setInt(1, self::worksheet_perm_id);
		$query->setInt(2, $userId);
		$query->setInt(3, self::worksheet_feat_id);

		$query->open();

		$rows = $query->getResultArray();

		if (count($rows) == 0 || ($groupFeatId = $rows[0]['group_feat_id']) == null) {
			return self::viewall_no_feat;
		}

		if (($groupPermId = $rows[0]['group_perm_id']) == null) {
			return self::viewall_no_perm;
		}

		return self::viewall_both;
	}

	public function execute() {
		$this->rows = null;

		switch ($this->getFeaturePermission($groupFeatId, $groupPermId)) {
			case self::viewall_no_feat:
				$this->rows = array();
				break;

			case self::viewall_no_perm:
				$this->rows = $this->loadAsOwner();
				break;

			case self::viewall_both:
				$this->rows = $this->loadAsAdmin();
				break;
		}
	}

	public function loadAsOwner() {
		$connection = $this->session->getConnection();
		$userId = $this->session->getVar('userId');

		$query = $connection->createQuery('
			select
				r.rest_id,
				r.rest_name,
				rt.type_id,
				rt.type_name,
				r.website,
				r.email,
				r.address,
				r.phone,
				r.fax,
				r.mobile
			from thank_u.restaurant r
				join thank_u.rest_type rt using (type_id)
			where r.user_id = ?
			order by r.rest_name
		');

		$query->setInt(1, $userId);

		$query->open();

		$rows = $query->getResultArray();

		return $rows;
	}

	public function loadAsAdmin() {
		$connection = $this->session->getConnection();
		$userId = $this->session->getVar('userId');

		$query = $connection->createQuery('
			select
				r.rest_id,
				r.rest_name,
				rt.type_id,
				rt.type_name,
				r.website,
				r.email,
				r.address,
				r.phone,
				r.fax,
				r.mobile
			from thank_u.restaurant r
				join thank_u.rest_type rt using (type_id)
			order by r.rest_name
		');

		$query->open();

		$rows = $query->getResultArray();

		return $rows;
	}

	public function getRows() {
		return $this->rows;
	}
}

class PassfileLoader {

	private $session;
	private $rows;

	public function __construct($session) {
		$this->session = $session;
	}

	public function execute() {
		$connection = $this->session->getConnection();
		$userId = $this->session->getVar('userId');

		$query = $connection->createQuery("
			select
				year(pc.sys_date) as year,
				month(pc.sys_date) as month,
				r.rest_id,
				concat(
					replace(lower(r.rest_name), ' ', '-'),
					'-',
					lpad(year(pc.sys_date), 4, '0'),
					'-',
					lpad(month(pc.sys_date), 2, '0'),
					'.pdf'
				) as filename
			from thank_u.user_setting us
				join thank_u.passcode pc using (rest_id)
				join thank_u.restaurant r using (rest_id)
			where us.user_id = ?
			and pc.sys_date > last_day(date_sub(current_date(), interval 1 month))
			group by year, month, r.rest_id
			order by year desc, month desc
		");

		$query->setInt(1, $userId);

		$query->open();

		$this->rows = $query->getResultArray();
	}

	public function getRows() {
		return $this->rows;
	}
}


class PasscodeLoader {

	
	private $session;
	private $restId;
	private $beginDate;
	private $endDate;
	private $rows;

        public function __construct($session) {
		$this->session = $session;
        }

	public function setBeginDate($beginDate) {
		$this->beginDate = $beginDate;
	}

	public function setEndDate($endDate) {
		$this->endDate = $endDate;
	}

	public function setDate($date) {
		$this->beginDate = $date;
		$this->endDate = $date;
	}

	public function execute() {
		$userId = $this->session->getVar('userId');
		$connection = $this->session->getConnection();
		$query = $connection->createQuery("
			select
				r.rest_name as name,
				p.sys_date as date,
				lpad(p.sequence, 3, '0') as sequence,
				p.password
			from thank_u.passcode p
				join thank_u.restaurant r using (rest_id)
				join thank_u.user_setting us using (rest_id)
			where us.user_id = ?
			and p.sys_date between ? and ?
			order by p.sys_date, p.sequence
		");


		$query->setInt(1, $userId);
		$query->setString(2, $this->beginDate);
		$query->setString(3, $this->endDate);

#		echo $query->getParsedSql();
#		exit;

		$query->open();

		$this->rows = $query->getResultArray();
	}

	public function getRows() {
		return $this->rows;
	}
}

class AppLoader {

	const appbrowser_feat_id = 6;
	const appbrowser_perm_id = 2;

	const viewall_unknown	= 0x00;
	const viewall_no_feat	= 0x01;
	const viewall_no_perm	= 0x02;
	const viewall_both	= 0x03;

	private $session;
	private $rows;

	private $limit;
	private $offset;

	public function __construct($session) {
		$this->session = $session;
		
		$this->limit = 50;
		$this->offset = 0;
	}

	public function getFeaturePermission(&$groupFeatId, &$groupPermId) {
		$connection = $this->session->getConnection();
		$userId = $this->session->getVar('userId');

		$query = $connection->createQuery('
			select
				gf.group_feat_id,
				gp.group_perm_id
			from access_control.user_group ug
				left join access_control.group_feature gf using (group_id)
				left join access_control.group_permission gp on gf.group_feat_id = gp.group_feat_id and gp.perm_id = ?
			where ug.user_id = ?
			and gf.feat_id = ?
			order by gp.group_perm_id desc
			limit 1
		');

		$query->setInt(1, self::appbrowser_perm_id);
		$query->setInt(2, $userId);
		$query->setInt(3, self::appbrowser_feat_id);

		$query->open();

		$rows = $query->getResultArray();

		if (count($rows) == 0 || ($groupFeatId = $rows[0]['group_feat_id']) == null) {
			return self::viewall_no_feat;
		}

		if (($groupPermId = $rows[0]['group_perm_id']) == null) {
			return self::viewall_no_perm;
		}

		return self::viewall_both;
	}

	public function execute() {
		$this->rows = null;

		switch ($this->getFeaturePermission($groupFeatId, $groupPermId)) {
			case self::viewall_no_feat:
				$this->rows = array();
				break;

			case self::viewall_no_perm:
				$this->rows = $this->loadAsSalesman();
				break;

			case self::viewall_both:
				$this->rows = $this->loadAsAdmin();
				break;
		}
	}

	public function loadAsSalesman() {
		$connection = $this->session->getConnection();
		$userId = $this->session->getVar('userId');

		$query = $connection->createQuery('
			select
				a.app_id,
				a.sys_timestamp,
				r.rest_name,
				t.stat_name
			from thank_u.application a
				join thank_u.restaurant r using (rest_id)
				join thank_u.app_status t using (stat_id)
				join thank_u.salesman s using (sale_id)
			where s.user_id = ?
			order by a.sys_timestamp desc
			limit 50
		');

		$query->setInt(1, $userId);

		$query->open();

		$rows = $query->getResultArray();

		return $rows;
	}

	public function loadAsAdmin() {
		$connection = $this->session->getConnection();

		$query = $connection->createQuery('
			select
				a.app_id,
				a.sys_timestamp,
				r.rest_name,
				t.stat_name
			from thank_u.application a
				join thank_u.restaurant r using (rest_id)
				join thank_u.app_status t using (stat_id)
			order by a.sys_timestamp desc
			limit 50
		');

		$query->open();

		$rows = $query->getResultArray();

		return $rows;
	}

	public function getRows() {
		return $this->rows;
	}
}

class CommissionLoader {

        private $session;
        private $rows;
        private $limit;
        private $offset;
	private $timeSlotId;

        public function __construct($session) {
                $this->session = $session;
                $this->limit = 50;
                $this->offset = 0;
                $this->timeSlotId = 0;
        }

        public function setTimeSlot($timeSlotId) {
                $this->timeSlotId = $timeSlotId;
        }

	public function loadAsSalesMan() {
                
		$userId = $this->session->getVar('userId');
		$connection = $this->session->getConnection();

		$query = $connection->createQuery('
			select
				t.time_slot_id as timeslotId,
				t.sys_date as sysdate,
				sum(coalesce(r.counter, 0)) as ctran,
				sum(coalesce(r.price, 0)) as price
			from thank_u.time_slot t
				left join thank_u.app_history h on h.sys_timestamp between t.begin_timestamp and t.end_timestamp
				left join thank_u.application a using (app_id)
				left join thank_u.salesman s on a.sale_id = s.sale_id and s.user_id = ?
				left join thank_u.app_transition r using (tran_id)
			where t.sys_date <= date_add(current_date, interval ((13 - dayofweek(current_date)) % 7) day)
			group by t.time_slot_id, t.sys_date
			order by t.sys_date desc
			limit ? offset ?;
		');

		$query->setInt(1, $userId);
		$query->setInt(2, $this->limit);
		$query->setInt(3, $this->offset);
	
		$query->open();

		$this->rows = $rows = $query->getResultArray();
	}

	public function loadAsSalesManDetail() {
                
		$userId = $this->session->getVar('userId');
		$connection = $this->session->getConnection();

		$query = $connection->createQuery('
			select
				h.sys_timestamp as systimestamp,
				re.rest_name as restname,
				tr.tran_name as tranname,
				tr.price as price
			from thank_u.time_slot t
				join thank_u.app_history h on h.sys_timestamp between t.begin_timestamp and t.end_timestamp
				join thank_u.application a using (app_id)
				join thank_u.salesman s using (sale_id)
				join thank_u.app_transition tr using (tran_id)
				join thank_u.restaurant re using (rest_id)
			where t.time_slot_id = ?
			and s.user_id = ?;
		');

		$query->setInt(1, $this->timeSlotId);
		$query->setInt(2, $userId);
	
		$query->open();

		$this->rows = $rows = $query->getResultArray();
	}

        public function getRows() {
                return $this->rows;
        }
}

?>
