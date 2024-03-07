<?php

require_once('com/moserv/sql/connection.php');
require_once('com/moserv/net/session.php');
require_once('com/moserv/util/web.php');
require_once('com/moserv/log/logger.php');

class FvcChartLoader {

	const fvc_yearly	= 0;
	const fvc_monthly	= 1;
	const fvc_daily		= 2;
	const fvc_hourly	= 3;

	public static $periods = array('%Y', '%m', '%d', '%H');

	private $session;
	private $period;
	private $beginTimestamp;
	private $endTimestamp;
	private $rows;
	private $categories;
	private $series;

	public function __construct($session) {
		$this->session = $session;
	}

	protected function setBeginTimestamp($beginTimestamp) {
		$this->beginTimestamp = $beginTimestamp;
	}

	protected function setEndTimestamp($endTimestamp) {
		$this->endTimestamp = $endTimestamp;
	}

	public function setYear($year) {
		$this->beginTimestamp = sprintf('%04d-01-01 00:00:00', $year);
		$this->endTimestamp = sprintf('%04d-12-31 23:59:59', $year);

		$this->period = FvcChartLoader::fvc_monthly;
	}

	public function setMonth($year, $month) {
		$this->beginTimestamp = sprintf('%04d-%02d-01 00:00:00', $year, $month);

		$date = sprintf('%04d-%02d-01', $year, $month);
		$day = date('t', strtotime($date));
		$this->endTimestamp = sprintf('%04d-%02d-%02d 23:59:59', $year, $month, $day);

		$this->period = FvcChartLoader::fvc_daily;
	}

	public function setDay($year, $month, $day) {
		$this->beginTimestamp = sprintf('%04d-%02d-%02d 00:00:00', $year, $month, $day);
		$this->endTimestamp = sprintf('%04d-%02d-%02d 23:59:59', $year, $month, $day);

		$this->period = FvcChartLoader::fvc_hourly;
	}


	public function execute() {
		$tag = FvcChartLoader::$periods[$this->period];
		$userId = $this->session->getVar('userId');

		$connection = $this->session->getConnection();

		$query = $connection->createQuery('
			select
				date_format(t.sys_timestamp, ?) as tag,
				t.times,
				count(1) as counter
			from thank_u.transaction t
				join thank_u.user_setting us using (rest_id)
			where t.sys_timestamp between ? and ?
			and us.user_id = ?
			group by tag, t.times
		');

		$query->setString(1, $tag);
		$query->setString(2, $this->beginTimestamp);
		$query->setString(3, $this->endTimestamp);
		$query->setInt(4, $userId);

		$query->open();

		$this->rows = $rows = $query->getResultArray();
/*
		$chunks = array();

		$prev = null;
		$freqs = null;

		$hash = array();

		foreach ($rows as $row) {
			if ($tag !== $prev) {
				$chunks[] = array(
					'tag'	=> $row['tag'],
#						'counter' => 0,
					'freqs' => array()
				);

#				$chunk = &$chunks[count($chunks) - 1];
#				$freqs = &$chunk['freqs'];
			}

			$chunks[count($chunks) - 1]['freqs'][] = array(
				'times' => $row['times'],
				'counter' => $row['counter']
			);

#				$chunk['counter'] += $row['counter'];
			$prev = $row['tag'];


			$hash[$row['times']] = true;
			$key = "{$row['times']} ครั้ง";
			if (array_key_exists($hash, $key)) {

			}
		}

		$this->rows = $chunks;
*/

		$categoriesHash = array();
		$timesHash = array();

		foreach ($rows as $row) {
			$tag = $row['tag'];
			$times = $row['times'];
			

			$categoriesHash[$tag] = 0;
			$timesHash[$times] = true;
		}

		$seriesHash = array();
		foreach (array_keys($timesHash) as $key) {
			$seriesHash[$key] = array(
				'name' => "$key ครั้ง",
				'data' => $categoriesHash
			);
		}


		$this->categories = $categories = array_keys($categoriesHash);


		foreach ($rows as $row) {
			$tag = $row['tag'];
			$times = $row['times'];
			$counter = $row['counter'];

			$seriesHash[$times]['data'][$tag] += $counter;
		}

		

		foreach (array_keys($seriesHash) as $key) {
			$seriesHash[$key]['data'] = array_values($seriesHash[$key]['data']);
		}

		$this->series = $series = array_values($seriesHash);
#		$this->series = $series = $seriesHash;
	}

	public function getCategories() {
		return $this->categories;
	}

	public function getSeries() {
		return $this->series;
	}

	public function getRows() {
		return $this->rows;
	}
}


class FvcTopLoader {

	private $session;
	private $rows;
	private $text;
	private $limit;
	private $offset;

	public function __construct($session) {
		$this->session = $session;
		$this->text = '';
		$this->limit = 50;
		$this->offset = 0;
	}

	public function setText($text) {
		$this->text = $text;
	}

	public function execute() {
		$userId = $this->session->getVar('userId');
		$text = ($this->text == null || $this->text == '')? '.*': $this->text;
		$connection = $this->session->getConnection();

		$query = $connection->createQuery("
			select
				f.rest_id as rest_id,
				f.sys_timestamp as tag,
				f.msisdn as msisdn,
				f.counter as counter
                        from thank_u.fvc f
                                join thank_u.user_setting us using (rest_id)
			where us.user_id = ?
			and concat('0', substr(msisdn, 3)) rlike ?
			order by f.counter desc, f.sys_timestamp desc
			limit ? offset ?
		");

		$query->setInt(1, $userId);
		$query->setString(2, $text);
		$query->setInt(3, $this->limit);
		$query->setInt(4, $this->offset);

		$query->open();

		$this->rows = $rows = $query->getResultArray();
	}

	public function getRows() {
		return $this->rows;
	}
}

class FvcTagLoader {
	private $session;

	public function __construct($session) {
		$this->session = $session;
	}

	public function getYears() {
		$userId = $this->session->getVar('userId');
		$connection = $this->session->getConnection();

		$query = $connection->createQuery('
			select
				year(f.sys_timestamp) as year
                        from thank_u.fvc f
                                join thank_u.user_setting us using (rest_id)
			where us.user_id = ?
			group by year
		');

		$query->setInt(1, $userId);

		$query->open();

		$this->rows = $rows = $query->getResultArray();
	}

	public function getMonths() {
		$userId = $this->session->getVar('userId');
		$connection = $this->session->getConnection();

		$query = $connection->createQuery('
			select
				year(f.sys_timestamp) as year,
				month(f.sys_timestamp) as month
                        from thank_u.fvc f
                                join thank_u.user_setting us using (rest_id)
			where us.user_id = ?
			group by year, month
		');

		$query->setInt(1, $userId);

		$query->open();

		$this->rows = $query->getResultArray();
	}

	public function getDay() {
		$userId = $this->session->getVar('userId');
		$connection = $this->session->getConnection();

		$query = $connection->createQuery('
			select
				year(f.sys_timestamp) as year,
				month(f.sys_timestamp) as month,
				day(f.sys_timestamp) as day
                        from thank_u.fvc f
                                join thank_u.user_setting us using (rest_id)
			where us.user_id = ?
			group by year, month, day
		');

		$query->setInt(1, $userId);

		$query->open();

		$this->rows = $query->getResultArray();
	}

}

class FvcDetailLoader {

	private $session;
	private $msisdn;
	private $rows;

	public function __construct($session) {
		$this->session = $session;
	}

	public function setMsisdn($msisdn) {
		$this->msisdn = $msisdn;
	}

	public function execute() {
		$connection = $this->session->getConnection();
		$userId = $this->session->getVar('userId');

		$query = $connection->createQuery("
			select
				t.sys_timestamp as timestamp,
				t.msisdn,
				t.passcode
			from thank_u.transaction t
				join thank_u.user_setting us using (rest_id)
			where us.user_id =?
			and t.msisdn = ?
		");

		$query->setInt(1, $userId);
		$query->setString(2, $this->msisdn);

		$query->open();

		$this->rows = $query->getResultArray();
	}

	public function getRows() {
		return $this->rows;
	}
}

?>
