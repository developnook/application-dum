<?php

require_once('com/moserv/sql/connection.php');
require_once('com/moserv/net/session.php');
require_once('com/moserv/util/web.php');
require_once('com/moserv/log/logger.php');

class FvcRestaurantLoader {
	private $session;
	private $beginDate;
	private $endDate;
	
	public function __construct($session){
		$this->session = $session;
	}

	public function setDay($year,$month,$day){
		$this->beginDate = sprintf('%04d-%02d-%02d', $year, $month, $day);
		$this->endDate = sprintf('%04d-%02d-%02d', $year, $month, $day);
	}
	
	public function setMonth($year ,$month){
		$this->beginDate = sprintf('%04d-%02d-01', $year, $month);
		$date = sprintf('%04d-%02d-01', $year, $month);
		$day = date('t', strtotime($date));
		$this->endDate = sprintf('%04d-%02d-%02d', $year, $month, $day);
	}

	public function setYear($year){
		$this->beginDate = sprintf('%04d-01-01', $year);
		$this->endDate = sprintf('%04d-12-31', $year);
	}

	public function loadRest(){
		$connection = $this->session->getConnection();
		$query = $connection->createQuery('
		select 
			r.rest_id, 
			r.rest_name
		from gumnan.restaurant r
			join gumnan.rest_type rt using (type_id)
		order by r.rest_id
		');
		$query->open();
		$rows = $query->getResultArray();
		return $rows;
	}
	
	public function loadCountNumber(){
		$v = $this->loadRest();
		for($i=0;$i<count($v);$i++){
			$connection = $this->session->getConnection();
			$query = $connection->createQuery("
				select 
					rt.rest_id,
					rt.rest_name,
					IFNULL(COUNT(t.msisdn),0) as sms
				from gumnan.restaurant as rt 
					INNER JOIN gumnan.user_setting as u ON rt.rest_id = u.rest_id 
					LEFT JOIN gumnan.tranc as t on rt.rest_id=t.rest_id 
				Where rt.rest_id=? 
				and t.sys_date between ? 
				and ?  
				group by rt.rest_id 
				order by rt.rest_id ASC;
			");
			$query->setString(1,$v[$i]['rest_id']);
			$query->setString(2,$this->beginDate);
			$query->setString(3,$this->endDate);
			$query->open();
			$row = $query->getResultArray();
		
			if($v[$i]['rest_id'] == @$row[0]['rest_id']){
				$a[$i]['rest_id']= $v[$i]['rest_id'];
				$a[$i]['rest_name'] = $v[$i]['rest_name'];
				$a[$i]['sms'] = @$row[0]['sms'];
			}else{
				$a[$i]['rest_id'] = $v[$i]['rest_id'];
				$a[$i]['rest_name'] = $v[$i]['rest_name'];
				$a[$i]['sms']=0;
			}
		}
	return $a;
	}
}

class FvcChartLoader {

	const fvc_yearly	= 0;
	const fvc_monthly	= 1;
	const fvc_daily		= 2;
	const fvc_hourly	= 3;

	public static $periods = array('%Y', '%m', '%d', '%H');

	private $session;
	private $period;
	private $beginDate;
	private $endDate;
	private $rows;
	private $categories;
	private $series;

	private $year;
	private $month;
	private $day;

	public function __construct($session) {
		$this->session = $session;

		$this->year = null;
		$this->month = null;
		$this->day = null;
	}

	protected function setBeginDate($beginDate) {
		$this->beginDate = $beginDate;
	}

	protected function setEndDate($endDate) {
		$this->endDate = $endDate;
	}

	public function setYear($year) {
		
		$this->beginDate = sprintf('%04d-01-01', $year);
		$this->endDate = sprintf('%04d-12-31', $year);

		$this->period = FvcChartLoader::fvc_monthly;

		$this->year = $year;
	}

	public function setMonth($year, $month) {

		$this->beginDate = sprintf('%04d-%02d-01', $year, $month);

		$date = sprintf('%04d-%02d-01', $year, $month);
		$day = date('t', strtotime($date));
		$this->endDate = sprintf('%04d-%02d-%02d', $year, $month, $day);

		$this->period = FvcChartLoader::fvc_daily;

		$this->year = $year;
		$this->month = $month;
	}

	public function setDay($year, $month, $day) {

		$this->beginDate = sprintf('%04d-%02d-%02d', $year, $month, $day);
		$this->endDate = sprintf('%04d-%02d-%02d', $year, $month, $day);

		$this->period = FvcChartLoader::fvc_hourly;

		$this->year = $year;
		$this->month = $month;
		$this->day = $day;
	}

	public function execute_admin($rest_id){
		$tag = FvcChartLoader::$periods[$this->period];
		$userId = $this->session->getVar('userId');

		$connection = $this->session->getConnection();
		$query = $connection->createQuery('
			select
				date_format(i.sys_timestamp, ?) as tag,
				t.times,
				count(1) as counter
			from gumnan.tranc t
				join gumnan.user_setting u using (rest_id)
				join message_service.incoming_message i using (incoming_message_id)
			where t.sys_date between ? and ?
			and u.rest_id = ?
			group by tag, t.times
		');

		$query->setString(1, $tag);
		$query->setString(2, $this->beginDate);
		$query->setString(3, $this->endDate);
//		$query->setInt(4, $userId);
		$query->setInt(4,$rest_id);
		$query->open();

		$this->rows = $rows = $query->getResultArray();

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

	
	public function execute() {
		$tag = FvcChartLoader::$periods[$this->period];
		$userId = $this->session->getVar('userId');

		$connection = $this->session->getConnection();

#		$query = $connection->createQuery('
#			select
#				date_format(t.sys_timestamp, ?) as tag,
#				t.times,
#				count(1) as counter
#			from gumnan.transaction t
#				join gumnan.user_setting us using (rest_id)
#			where t.sys_timestamp between ? and ?
#			and us.user_id = ?
#			group by tag, t.times
#		');


		$query = $connection->createQuery('
			select
				date_format(i.sys_timestamp, ?) as tag,
				t.times,
				count(1) as counter
			from gumnan.tranc t
				join gumnan.user_setting u using (rest_id)
				join message_service.incoming_message i using (incoming_message_id)
			where t.sys_date between ? and ?
			and u.user_id = ?
			group by tag, t.times
		');

		$query->setString(1, $tag);
		$query->setString(2, $this->beginDate);
		$query->setString(3, $this->endDate);
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

	protected function getTransforms() {
		$transforms = null;

		switch ($this->period) {
			case FvcChartLoader::fvc_yearly: break;

			case FvcChartLoader::fvc_monthly:
				$transforms = array(
					1	=> 'ม.ค.',
					2	=> 'ก.พ.',
					3	=> 'มี.ค.',
					4	=> 'เม.ย.',
					5	=> 'พ.ค.',
					6	=> 'มิ.ย.',
					7	=> 'ก.ค.',
					8	=> 'ส.ค.',
					9	=> 'ก.ย.',
					10	=> 'ต.ค.',
					11	=> 'พ.ย.',
					12	=> 'ธ.ค.'
				);
				
			break;

			case FvcChartLoader::fvc_daily: break;

			case FvcChartLoader::fvc_hourly:
				$transforms = array();
				for ($i = 0; $i < 24; $i++) {
					$transforms[i] = sprintf('%02d:00');
				}
			break;
		}

		return $transforms;
	}


	public function getCategories($full = false, $tran = false) {
		$categories = null;

		if ($full) {
			switch ($this->period) {
				case FvcChartLoader::fvc_yearly:
					$categories = $this->categories;
				break;

				case FvcChartLoader::fvc_monthly:
#					$categories = array_values(FvcChartLoader::$months);
					$categories = range(1, 12);
				break;

				case FvcChartLoader::fvc_daily:
					$date = sprintf('%04d-%02d-01', $this->year, $this->month);
					$categories = range(1, date('t', strtotime($date)) + 0);
				break;

				case FvcChartLoader::fvc_hourly:
					 $categories = range(0, 23);
				break;

			}
		}
		else
			$categories = $this->categories;

		if ($tran && (($transforms = $this->getTransforms()) != null)) {
			$cats = array();

			foreach ($categories as $key => $value) {
				$cats[] = $transforms[$value];
			}

			$categories = $cats;
		}

		return $categories;
	}

	protected function merge($fullCats, $slimCats, $data) {
		$dst = array();

		foreach ($fullCats as $value) {
			$dst[] = (($pos = array_search($value, $slimCats)) === false)? 0: $data[$pos];
		}

		return $dst;
	}

	public function getSeries($full = false) {
		$series = null;

		if ($full) {
			$slimCats = $this->getCategories(false);
			$fullCats = $this->getCategories(true);

#			$index = (count($slimCats) == 0 || count($fullCats) == 0)? 0: array_search($slimCats[0], $fullCats);

#			Logger::$logger->info('slimcat = '.print_r($slimCats, true));
#			Logger::$logger->info('fullcat = '.print_r($fullCats, true));
#			print_r($slimCats);
#			print_r($fullCats);
#			exit;

#			$head = array_fill(0, $index, 0);
#			$tail = array_fill(0, count($fullCats) - count($slimCats) - count($head), 0);

			$series = array();

			if (empty($this->series)) {
				$series[] = array(
					'name' => '',
					'data' => $this->merge($fullCats, $slimCats, array())
				);
			}
			else {
				foreach ($this->series as $serie) {
					$series[] = array(
						'name' => $serie['name'],
						'data' => $this->merge($fullCats, $slimCats, $serie['data'])
					);
				}
			}

		}
		else
			$series = $this->series;

		return $series;
	}

	public function getRows() {
		return $this->rows;
	}
}

class FvcTimePeriodLoader {

	const fvc_yearly	= 0;
	const fvc_monthly	= 1;
	const fvc_daily		= 2;
	const fvc_hourly	= 3;

	public static $periods = array(
		array(
			'format'	=> '%Y',
			'label'		=> 'ปี %1$04d'
		),
		array(
			'format'	=> '%Y-%m',
			'label'		=> '%2$s %1$04d'
		),
		array(
			'format'	=> '%Y-%m-%d',
			'label'		=> '%3$d %2$s %1$04d'
		),
		array(
			'format'	=> '%Y-%m-%d %H',
			'label'		=> '%4$02d:00 %3$d %2$s %1$04d'
		)
	);

	public static $mons = array(
					1	=> 'มกราคม',
					2	=> 'กุมภาพันธ์',
					3	=> 'มีนาคม',
					4	=> 'เมษายน',
					5	=> 'พฤษภาคม',
					6	=> 'มิถุนายน',
					7	=> 'กรกฎาคม',
					8	=> 'สิงหาคม',
					9	=> 'กันยายน',
					10	=> 'ตุลาคม',
					11	=> 'พฤศจิกายน',
					12	=> 'ธันวาคม'
				);

	private $session;
	private $period;
	private $rows;

	public function __construct($session) {
		$this->session = $session;
	}

	public function setPeriod($period) {
		$this->period = $period;
	}

	public function execute() {
		$format = FvcTimePeriodLoader::$periods[$this->period]['format'];
		$userId = $this->session->getVar('userId');
		$connection = $this->session->getConnection();

		$query = $connection->createQuery("
			select
				tag
			from (
				select
					date_format(current_date, ?) as tag
				from dual

				union

				select
					date_format(sys_date, ?) as tag
				from gumnan.tranc t
					join gumnan.user_setting u using (rest_id)
				where u.user_id = ?
				group by tag
			) x
			order by tag desc
		");

		$query->setString(1, $format);
		$query->setString(2, $format);
		$query->setInt(3, $userId);

		$query->open();

		$rows = $query->getResultArray();
		$this->rows = array();
		$label = FvcTimePeriodLoader::$periods[$this->period]['label'];

		foreach ($rows as $row) {
			$tag = $row['tag'];

			if (preg_match('/^([0-9]{4})(?:-([0-9]{2})(?:-([0-9]{2})(?: ([0-9]{2}))?)?)?/', $tag, $groups)) {

				@list($date, $yyyy, $mm, $dd, $hh) = $groups;

				$yyyy += 543;

				$mm += 0;
				$mon = FvcTimePeriodLoader::$mons[$mm];

				$dd += 0;

				$value = sprintf($label, $yyyy, $mon, $dd, $hh);

				$this->rows[] = array(
					'label'		=> $value,
					'value'		=> $tag,
					'selected'	=> false
				);
			}
		}

		$this->rows[0]['label'] .= ' (ปัจจุบัน)';
		$this->rows[0]['selected'] = true;
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

#	public function execute() {
#		$userId = $this->session->getVar('userId');
#		$text = ($this->text == null || $this->text == '')? '.*': $this->text;
#		$connection = $this->session->getConnection();
#
#		$query = $connection->createQuery("
#			select
#				f.rest_id as rest_id,
#				f.sys_timestamp as tag,
#				f.msisdn as msisdn,
#				f.counter as counter
#			from gumnan.fvc f
#				join gumnan.user_setting us using (rest_id)
#			where us.user_id = ?
#			and concat('0', substr(msisdn, 3)) rlike ?
#			order by f.counter desc, f.sys_timestamp desc
#			limit ? offset ?
#		");
#
#		$query->setInt(1, $userId);
#		$query->setString(2, $text);
#		$query->setInt(3, $this->limit);
#		$query->setInt(4, $this->offset);
#
#		$query->open();
#
#		$this->rows = $rows = $query->getResultArray();
#	}

	public function execute() {
		$userId = $this->session->getVar('userId');
		$text = ($this->text == null || $this->text == '')? '.*': $this->text;
		$connection = $this->session->getConnection();

#		$query = $connection->createQuery("
#			select
#				t.rest_id,
#				t.msisdn as msisdn,
#				max(t.sys_timestamp) as tag,
#				count(*) as counter
#			from gumnan.transaction t
#				join gumnan.user_setting us using (rest_id)
#			where us.user_id = ?
#			and concat('0', substr(t.msisdn, 3)) rlike ?
#			group by t.rest_id, t.msisdn
#			order by counter desc, tag desc
#			limit ? offset ?
#		");

		$query = $connection->createQuery("
			select
				t.rest_id,
				t.msisdn as msisdn,
				max(t.sys_date) as tag,
				count(*) as counter
			from gumnan.tranc t
				join gumnan.user_setting u using (rest_id)
			where u.user_id = ?
			and concat('0', substr(t.msisdn, 3)) rlike ?
			group by t.rest_id, t.msisdn
			order by counter desc, tag desc
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
                        from gumnan.fvc f
                                join gumnan.user_setting u using (rest_id)
			where u.user_id = ?
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
                        from gumnan.fvc f
                                join gumnan.user_setting u using (rest_id)
			where u.user_id = ?
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
                        from gumnan.fvc f
                                join gumnan.user_setting u using (rest_id)
			where u.user_id = ?
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

#		$query = $connection->createQuery("
#			select
#				t.sys_timestamp as timestamp,
#				t.msisdn,
#				t.passcode
#			from gumnan.transaction t
#				join gumnan.user_setting us using (rest_id)
#			where us.user_id =?
#			and t.msisdn = ?
#			order by t.sys_timestamp desc
#		");

		$query = $connection->createQuery("
			select
				i.sys_timestamp as timestamp,
				t.msisdn,
				concat(lpad(p.sequence, 3, '0'), '-', p.password) as passcode
			from gumnan.tranc t
				join gumnan.user_setting u using (rest_id)
				join gumnan.passcode p using (passcode_id)
				join message_service.incoming_message i using (incoming_message_id)
			where u.user_id = ?
			and t.msisdn = ?
			order by i.sys_timestamp desc
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
