<?php

require_once('com/moserv/cache/cache.php');
require_once('com/moserv/log/logger.php');
require_once('com/moserv/net/session.php');
require_once('com/moserv/net/url.php');
require_once('com/moserv/sql/connection.php');
require_once('com/moserv/wap/wap-media.php');


abstract class ClickBlocker {

	const blk_none		= 0x00;
	const blk_black		= 0x01;
	const blk_climit	= 0x02;
	const blk_tlimit	= 0x03;
	const blk_temp		= 0x04;
	const blk_lastsub	= 0x05;
	const blk_proxy		= 0x06;
	const blk_slimit	= 0x07;
	const blk_alimit	= 0x08;
	const blk_hps		= 0x0a;
	const blk_cpd		= 0x0b;
	const blk_last_msisdn	= 0x0c;

	protected $session;
	protected $msisdn;
	protected $telcoId;

	protected $clickId;

	protected $auxCode;

	protected $timestamp;
	protected $date;
	protected $time;

	protected $serviceId;

	public function __construct($session) {
		$this->session = $session;
		$this->telcoId = WapMedia::telco_unk;
		$this->auxCode = 0;

		$this->servicerId = 0;

		$this->timestamp = (new DateTime())->format('Y-m-d H:i:s.u');
		list($this->date, $this->time) = explode(' ', $this->timestamp);
	}


	public function setMsisdn($msisdn) {
		$this->msisdn = $msisdn;
	}

	public function setTelcoId($telcoId) {
		$this->telcoId = $telcoId;
	}

	public function setClickId($clickId) {
		$this->clickId = $clickId;
	}

	public function setServiceId($serviceId) {
		$this->serviceId = $serviceId;
	}

	protected abstract function doExecute();

	protected function blockLog($code) {
		$url = new Url();
		$telco = (WapMedia::$telcos[$this->session->getTelcoId()] == "")? 'nul': WapMedia::$telcos[$this->session->getTelcoId()];
		$msisdn = ($this->session->getMsisdn() == '')? 'unknown': $this->session->getMsisdn();

		$line = sprintf(
			"C-BLOCK T(%s), M(%s) C(%02d) - %s%s",
				$telco,
				substr($msisdn, 0, 4).'-'.substr($msisdn, 4, 3).'-'.substr($msisdn, 7),
				$code,
				$url->getHost(),
				$url->getPath()
		);

		$this->session->takeLog($line);
	}

	protected function save($blockCode, $auxCode = 0) {
		if (empty($this->msisdn)) {
			return;
		}

		$connection = $this->session->getConnection();
		$query = $connection->createQuery(
<<<sql
			insert ignore into wap.block_click (
				msisdn,
				sys_timestamp,
				click_id,
				block_code,
				aux_code
			)
			values (?, ?, ?, ?, ?)
sql
		);

		$query->setInt(1, $this->msisdn);
		$query->setString(2, $this->timestamp);
		$query->setInt(3, $this->clickId);
		$query->setInt(4, $blockCode);
		$query->setInt(5, $auxCode);

		$query->open();


		$query = $connection->createQuery(
<<<sql
			insert into wap.campaign_block (
				sys_date,
				campaign_id,
				block_code,
				aux_code,

				sys_time,
				hit_id
			)
			select
				x.sys_date,
				t.campaign_id,
				x.block_code,
				x.aux_code,

				x.sys_time,
				x.hit_id
			from (
				select
					? as click_id,
					? as sys_date,
					? as block_code,
					? as aux_code,

					? as sys_time,
					? as hit_id
				from dual
			) x
				join wap.click c on c.click_id = x.click_id and c.sys_timestamp between ? and ?
				join wap.click_tag t using (click_tag_id)
				left join wap.campaign_block n using (sys_date, campaign_id, block_code, aux_code)
			where n.campaign_block_id is null
sql
		);

		$query->setInt(1, $this->clickId);
		$query->setString(2, $this->date);
		$query->setInt(3, $blockCode);
		$query->setInt(4, $auxCode);
		$query->setString(5, $this->time);
		$query->setInt(6, $this->session->getHitId());
		$query->setString(7, "{$this->date} 00:00:00");
		$query->setString(8, "{$this->date} 23:59:59");

		$query->open();
	}


	public function execute($saveRevisit = true) {
		$code = ClickBlocker::blk_none;

		if (($code = $this->doExecute()) != ClickBlocker::blk_none && $saveRevisit) {
			$this->save($code, $this->auxCode);

			$this->blockLog($code);
		}

		return $code;
	}

	public function getAuxCode() {
		return $this->auxCode;
	}
}


class TempClickBlocker extends ClickBlocker {

	protected function doExecute() {
		$url = new Url();

##		return ($this->telcoId == WapMedia::telco_ais)? ClickBlocker::blk_temp: ClickBlocker::blk_none;
##		return ($this->telcoId == WapMedia::telco_dtc)? ClickBlocker::blk_temp: ClickBlocker::blk_none;
##		return ($this->telcoId == WapMedia::telco_dtc && $url->getHost() == 'm.kangped.com')? ClickBlocker::blk_temp: ClickBlocker::blk_none;

		
		return ($this->telcoId == WapMedia::telco_dtc && ($this->serviceId != 601 && $this->serviceId != 0) ? ClickBlocker::blk_temp: ClickBlocker::blk_none);
#		return ClickBlocker::blk_none;
	}


}


class BlacklistClickBlocker extends ClickBlocker {

	protected function doExecute() {
		$connection = $this->session->getConnection();

		$query = $connection->createQuery(
<<<sql
			select
				msisdn
			from wap.block_msisdn
			where msisdn = ?
			and enabled = 1
sql
		);

		$query->setString(1, $this->msisdn);

		$query->open(false);

		$rows = $query->getResultArray();

		$blocked = (count($rows) > 0);


		return ($blocked)? ClickBlocker::blk_black: ClickBlocker::blk_none;
	}
}

class ProxyClickBlocker extends ClickBlocker {

	protected function doExecute() {
		$remoteAddress = $this->session->getRemoteAddress();
#		$addresses = preg_split('/[\s,]+/', $remoteAddress);
		$addresses = preg_split('/\s*,\s*/', $remoteAddress);

		return (count($addresses) > 1)? ClickBlocker::blk_proxy: ClickBlocker::blk_none;
	}
}

abstract class LimitClickBlocker extends ClickBlocker {

	protected $limit;

	public function __construct($session) {
		parent::__construct($session);

		$this->limit = 2;
	}


	public function setLimit($limit) {
		$this->limit = $limit;
	}

	public function getLimit() {
		return $this->limit;
	}
}

class SubLimitClickBlocker extends LimitClickBlocker {

	protected function doExecute() {

		$connection = $this->session->getConnection();
		$query = $connection->createQuery(
<<<sql
			select
				subscriber_id
			from message_service.subscriber
			where msisdn = ?
			and enabled = ?
sql
		);

		$query->setString(1, $this->msisdn);
		$query->setInt(2, 1); # always enabled

		$query->open(false);

		$rows = $query->getResultArray();

		$blocked = (count($rows) >= $this->getLimit());

		return ($blocked)? ClickBlocker::blk_climit: ClickBlocker::blk_none;
	}
}


class TryLimitClickBlocker extends LimitClickBlocker {

	protected function doExecute() {

		$connection = $this->session->getConnection();
		$query = $connection->createQuery(
<<<sql
			select
				subscriber_id
			from message_service.subscriber
			where msisdn = ?
			and enabled = 0
			and unregister_timestamp >= date_sub(current_timestamp(), interval 2 day)
sql
		);

		$query->setString(1, $this->msisdn);

		$query->open(false);

		$rows = $query->getResultArray();

		$blocked = (count($rows) >= $this->getLimit());

		return ($blocked)? ClickBlocker::blk_tlimit: ClickBlocker::blk_none;
	}
}

class HPSLimitClickBlocker extends LimitClickBlocker {

	public static $limits = array(
		WapMedia::telco_unk => 100,
		WapMedia::telco_non => 100,
		WapMedia::telco_mos => 100,
		WapMedia::telco_ais => 100,
		WapMedia::telco_dtc => 100,
		WapMedia::telco_tmv => 100,
		WapMedia::telco_rmv => 100
	);

	protected function doExecute() {
		$connection = $this->session->getConnection();
		$cache = $this->session->getCache();

		$key = sprintf('HPS-T(%s:%s)_%s', WapMedia::$telcos[$this->telcoId], $this->session->getCpId(),  date("Y-m-d_H:i:s"));

		if (($counter = $cache->inc($key)) === FALSE) {
			$cache->set($key, 1, 0, 60 * 30);
			$counter = 1;
		}

		$this->session->takeLog("KEY ". $key . " " . $counter);

		$blocked = ($counter > $this->getLimit());

		return ($blocked)? ClickBlocker::blk_hps: ClickBlocker::blk_none;
	}

	public function getLimit() {
		return HPSLimitClickBlocker::$limits[$this->telcoId];
	}
}

class LastSubLimitClickBlocker extends LimitClickBlocker {

	public static $limits = array(
		WapMedia::telco_unk => 0,
		WapMedia::telco_non => 0,
		WapMedia::telco_mos => 0,
		WapMedia::telco_ais => 0,
		WapMedia::telco_dtc => 120, # mo have changed from 45 to 15, 0  and finally 15 on 5:05pm 21jun
		WapMedia::telco_tmv => 0,
		WapMedia::telco_rmv => 0
#		WapMedia::telco_tmv => 60 * 60 * 24 * 7,
#		WapMedia::telco_rmv => 60 * 60 * 24 * 7
	);

	protected function doExecute() {

		if (($limit = $this->getLimit()) == 0) {
			return ClickBlocker::blk_none;
		}


		$cpId = $this->session->getCpId();


		$connection = $this->session->getConnection();
#		$query = $connection->createQuery("
#			select
#				time_to_sec(timediff(current_timestamp(), s.register_timestamp)) as diff
#			from message_service.subscriber s
#				join message_service.telco_service t using (telco_id, service_id)
#			where s.telco_id = ?
#			and t.shortcode like ?
#			order by s.subscriber_id desc
#			limit 1
#		");
		$query = $connection->createQuery(
<<<sql
			select
				time_to_sec(timediff(current_timestamp(), s.register_timestamp)) as diff
			from message_service.subscriber s
			where s.telco_id = ?
			order by s.subscriber_id desc
			limit 1
sql
		);

		$query->setInt(1, $this->telcoId);
#		$query->setString(2, "4{$cpId}%");

		$query->open(false);
		$rows = $query->getResultArray();

		$blocked = (count($rows) > 0 && $rows[0]['diff'] < $limit);

		return ($blocked)? ClickBlocker::blk_lastsub: ClickBlocker::blk_none;
	}

	public function getLimit() {
		return LastSubLimitClickBlocker::$limits[$this->telcoId];
	}
}

class CPDLimitClickBlocker extends LimitClickBlocker {

	public static $limits = array(
		WapMedia::telco_unk => PHP_INT_MAX,
		WapMedia::telco_non => PHP_INT_MAX,
		WapMedia::telco_mos => PHP_INT_MAX,
		WapMedia::telco_ais => 50,
		WapMedia::telco_dtc => 30,
		WapMedia::telco_tmv => 1,
		WapMedia::telco_rmv => 1
	);

	protected function doExecute() {
		$limit = $this->getLimit();

		if ($limit == PHP_INT_MAX) {
			return ClickBlocker::blk_none;
		}

		$connection = $this->session->getConnection();
		$cache = $this->session->getCache();

#		$key = sprintf('HPS-T(%s:%s)_%s', WapMedia::$telcos[$this->telcoId], $this->session->getCpId(),  date("Y-m-d_H:i:s"));
		$key = sprintf('CPD-T(%s:%s)_%s_%s', WapMedia::$telcos[$this->telcoId], $this->session->getCpId(), $this->session->getMsisdn(),  date("Y-m-d"));
#		$key = sprintf('CPD-T(%s:%s)_%s_%s', WapMedia::$telcos[$this->telcoId], $this->session->getCpId(), $this->session->getMsisdn(),  date("Y-m-d H"));

		if (($counter = $cache->inc($key)) === FALSE) {
			$cache->set($key, 1, 0, 60 * 60 * 24);
#			$cache->set($key, 1, 0, 60 * 60);
			$counter = 1;
		}

		$this->session->takeLog("KEY ". $key . " " . $counter);

		$blocked = ($counter > $limit);

		return ($blocked)? ClickBlocker::blk_cpd: ClickBlocker::blk_none;
	}

	public function getLimit() {
		return CPDLimitClickBlocker::$limits[$this->telcoId];
	}
}


class LastMsisdnLimitClickBlocker extends LimitClickBlocker {

	public static $limits = array(
		WapMedia::telco_unk => 0,
		WapMedia::telco_non => 0,
		WapMedia::telco_mos => 0,
		WapMedia::telco_ais => 0,
		WapMedia::telco_dtc => 7,
		WapMedia::telco_tmv => 7,
		WapMedia::telco_rmv => 7
	);

	protected function doExecute() {

		$dayLimit = $this->getLimit();

		if ($dayLimit <= 0) {
			return ClickBlocker::blk_none;
		}

		$connection = $this->session->getConnection();
		$query = $connection->createQuery(
<<<sql
			select
				case
					when enabled = 0 and  current_timestamp < date_add(unregister_timestamp, interval ? day) then 1
					else 0
				end as is_block
			from message_service.subscriber
			where msisdn = ?
			order by subscriber_id desc
			limit 1
sql
		);

		$query->setInt(1, $dayLimit);
		$query->setString(2, $this->msisdn);

		$query->open(false);

		$rows = $query->getResultArray();

		$blocked = (count($rows) > 0 && $rows[0]['is_block'] == 1);

		return ($blocked)? ClickBlocker::blk_last_msisdn: ClickBlocker::blk_none;
	}

	public function getLimit() {
		return LastMsisdnLimitClickBlocker::$limits[$this->telcoId];
	}
}

abstract class CounterLimitClickBlocker extends ClickBlocker {

	public static $configs = array(
		'statement' => array(
			'telco_id'	=> array('default' => -1,	'field' => 's.telco_id'),
			'provider_id'	=> array('default' => -1,	'field' => 'v.provider_id'),
			'shortcode'	=> array('default' => 'XXXXXXX','field' => 'v.shortcode'),
			'service_id'	=> array('default' => -1,	'field' => 'v.service_id'),
			'affilite_id'	=> array('default' => -1,	'field' => 's.affiliate_id')
		)
	);


#	protected $timestamp;
#	protected $date;
#	protected $time;
#
#	public function __construct($session) {
#		parent::__construct($session);
#
#		$this->timestamp = date('Y-m-d H:i:s');
#		list($this->date, $this->time) = explode(' ', $this->timestamp);
#	}

	protected function getCounterConfigs($limitTypeName) {

		$connection = $this->session->getConnection();
		$query = $connection->createQuery(
<<<sql
			select
				l.click_limit_id,
				l.counter,
				l.telco_id,
				l.provider_id,
				l.shortcode,
				l.service_id,
				l.affiliate_id
			from wap.click_limit l
				join wap.limit_type e on e.limit_type_id = l.limit_type_id

				join wap.click c on c.click_id = ? and c.sys_timestamp between ? and ?
				join wap.hit h on h.hit_id = c.hit_id and h.sys_timestamp between ? and ?
				join wap.page p on p.page_id = h.page_id
				join wap.affiliate a on a.affiliate_id = p.affiliate_id
				join wap.click_tag t on t.campaign_id = p.campaign_id and t.rank = 0
				join message_service.telco_service s on s.service_id = t.service_id and s.telco_id = ?
			where e.limit_type_name = ?
			and l.enabled = 1
			and e.enabled = 1
			and (l.telco_id = -1 or l.telco_id = s.telco_id)
			and (l.provider_id = -1 or l.provider_id = s.provider_id)
			and (l.shortcode = 'XXXXXXX' or l.shortcode = s.shortcode)
			and (l.service_id = -1 or l.service_id = s.service_id)
			and (l.affiliate_id = -1 or l.affiliate_id = p.affiliate_id)
			order by l.telco_id, l.shortcode, l.service_id, l.affiliate_id desc
			limit 1
sql
		);

		$query->setInt(1, $this->clickId);
		$query->setString(2, "{$this->date} 00:00:00");
		$query->setString(3, "{$this->date} 23:59:59");
		$query->setString(4, "{$this->date} 00:00:00");
		$query->setString(5, "{$this->date} 23:59:59");
		$query->setInt(6, $this->session->getTelcoId());
		$query->setString(7, $limitTypeName);

#		$query->open(false); # no use of memcache
		$query->open(true, 30); # use memcache for 30 seconds

		$rows = $query->getResultArray();

#		return (count($rows[0]) > 0)? $rows[0]: null;
		return $rows;
	}

	abstract protected function getSummaryField();
	abstract protected function getLimitTypeName();
	abstract protected function getBlockCode();

	protected function getBeginTimestamp() {
		return "{$this->date} 00:00:00";
	}

	protected function getEndTimestamp() {
#		return $this->timestamp; # sql will not be cached
		return "{$this->date} 23:59:59";  # sql will be cached
	}

	protected function doTest($blockCode, $limitTypeName, $counterConfig, $summaryField) {

		$config = CounterLimitClickBlocker::$configs['statement'];
		$tokens = array(
<<<sql
			select
				coalesce(sum({$summaryField}), 0) as sss,
				coalesce(sum({$summaryField}), 0) >= ?  as is_block
			from wap.hit_summary s force index (u_hit_summary)
				join wap.click_tag t on t.campaign_id = s.campaign_id and t.rank = 0
				join message_service.telco_service v on v.telco_id = s.telco_id and v.service_id = t.service_id 
			where s.summary_timestamp between ? and ?
sql
		);

		foreach ($counterConfig as $key => $value) {
			if (array_key_exists($key, $config)) {
				$lconfig = $config[$key];

				if ($value != $lconfig['default']) {
					$tokens[] = "and {$lconfig['field']} = '{$value}'\n";
				}
			}
		}

		$sql = implode('', $tokens);

		$connection = $this->session->getConnection();
		$query = $connection->createQuery($sql);

		$query->setInt(1, $counterConfig['counter']);
		$query->setString(2, $this->getBeginTimestamp());
		$query->setString(3, $this->getEndTimestamp());

#		$query->open(false); # no use of memcache
#		$query->open(true, 5 * 60); # use memcache for 5 minutes
		$query->open(true, 10 * 60); # use memcache for 5 minutes

		$rows = $query->getResultArray();

#		echo $query->getParsedSql();
#		print_r($rows);
#
#		exit;

		if (count($rows) == 0 || $rows[0]['is_block'] == 0) {
			return ClickBlocker::blk_none;
		}
		else {
			$this->auxCode = $counterConfig['click_limit_id'];

			return $blockCode;
		}

#		return (count($rows) == 0 || $rows[0]['is_block'] == 0)? ClickBlocker::blk_none: $blockCode;
	}


	protected function doExecute() {
		$blockCode = $this->getBlockCode();
		$limitTypeName = $this->getLimitTypeName();
		$counterConfigs = $this->getCounterConfigs($limitTypeName);
		$summaryField = $this->getSummaryField();
		$code = ClickBlocker::blk_none;

		foreach ($counterConfigs as $counterConfig) {
			$code = $this->doTest($blockCode, $limitTypeName, $counterConfig, $summaryField);

			if ($code != ClickBlocker::blk_none) {
				break;
			}
		}

		return $code;
	}

#	protected function save($blockCode, $auxCode = 0) {
#		parent::save($blockCode, $auxCode);
#
#		$connection = $this->session->getConnection();
#		$query = $connection->createQuery(
#<<<sql
#			insert into wap.daily_block_campaign (
#				sys_date,
#				campaign_id,
#				block_code,
#				aux_code,
#
#				sys_timestamp,
#				hit_id
#			)
#			select
#				x.sys_date,
#				t.campaign_id,
#				x.block_code,
#				x.aux_code,
#
#				x.sys_timestamp,
#				x.hit_id
#			from (
#				select
#					? as click_id,
#					? as sys_date,
#					? as block_code,
#					? as aux_code,
#
#					? as sys_time,
#					? as hit_id
#				from dual
#			) x
#				join wap.click c on c.click_id = x.click_id and c.sys_timestamp between ? and ?
#				join wap.click_tag t using (click_tag_id)
#				left join wap.block_campaign n using (sys_date, campaign_id, block_code, aux_code)
#			where n.block_campaign_id is null
#sql
#		);
#
#		$query->setInt(1, $this->clickId);
#		$query->setString(2, $this->date);
#		$query->setInt(3, $blockCode);
#		$query->setInt(4, $auxCode);
#		$query->setString(5, $this->time);
#		$query->setInt(6, $this->session->getHitId());
#		$query->setString(7, "{$this->date} 00:00:00");
#		$query->setString(8, "{$this->date} 23:59:59");
#
#		$query->open();
#	}
}

class SubscriberCounterLimitClickBlocker extends CounterLimitClickBlocker {

	protected function getSummaryField() {
		return 's.subs';
	}

	protected function getBlockCode() {
		return ClickBlocker::blk_slimit;
	}

	protected function getLimitTypeName() {
		return 'DAILY_SUBS';
	}
}

class AttemptCounterLimitClickBlocker extends CounterLimitClickBlocker {

	protected function getSummaryField() {
		return 's.clicks - s.blocks';
	}

	protected function getBlockCode() {
		return ClickBlocker::blk_alimit;
	}

	protected function getLimitTypeName() {
		return 'DAILY_ATMS';
	}
}

class WapClickBlocker extends ClickBlocker {
	protected function doExecute() {

#		if ($this->msisdn == '66817204478') {
#			$b = new SubscriberCounterLimitClickBlocker($this->session);
#			$b->setMsisdn($this->msisdn);
#			$b->setTelcoId($this->telcoId);
#			$b->setClickId($this->clickId);
#
#			$code = $b->execute(false);
#
#			echo $code;
#			exit;
#		}


		if (
			empty($this->msisdn)		||
			$this->msisdn == "66945285427"	||	# moserv test phone dtac
			$this->msisdn == "66868164860"	||	# moserv test phone #5
			$this->msisdn == "66910033942"	||	# ice test (true) #5
			$this->msisdn == "66617245144"	||	# moserv dtac test
			$this->msisdn == "66660054036"	||	# moserv dtac test 2
			$this->msisdn == "66869743416"	||	# man dtac
			$this->msisdn == "66922637316"	||	# man ais
			$this->msisdn == "66817204478"	||	# mo ais
			$this->msisdn == "66625751341"	||	# moserv test phone ais
			$this->msisdn == "66815785889"	||	# kai dtac
			$this->msisdn == "66624873810"	||	# moserv ais test phone
			$this->msisdn == "66622944908"	||	# ae ais
#			$this->msisdn == "66871276524"	||	# p alex ais
			$this->msisdn == "66954911485"	||	# moserv test phone (true)
			$this->msisdn == "66989234406"	||	# moserv test phone#1 (ais moserv-owned)
			$this->msisdn == "66818312138"	||	# moserv test phone#2 (ais ais-owned)
			$this->msisdn == "66815120524"	||	# moserv test phone#3 (ais ais-owned)
			$this->msisdn == "66818333336"	||	# moserv test phone#4 (ais ais-owned)
			$this->msisdn == "66899201960"	||	# AIS test phone (ais)
			$this->msisdn == "66865630349"	||	# truemove owner (true)
			$this->msisdn == "66629794094"	||	# kib nong kai
			$this->msisdn == "66894851777"		# k.oh dtac
		) {
			return ClickBlocker::blk_none;
		}


#		if ($this->telcoId == 3) {
##			return ClickBlocker::blk_temp;
#			return ClickBlocker::blk_none;
#		}


//		$url = new Url();
//
//		if (preg_match('//', $url->toString())) {
//			return ClickBlocker::blk_none;
//		}
//

#		if ($this->telcoId == 3) {
#			return 20;
#		}

		$code = ClickBlocker::blk_none;


		$blocker = new TempClickBlocker($this->session);
		$blocker->setMsisdn($this->msisdn);
		$blocker->setTelcoId($this->telcoId);
		if (($code = $blocker->execute(false)) != ClickBlocker::blk_none) {
			Logger::$logger->info("blocked [{$this->msisdn}] by Temp");

			return $code;
		}

		$blocker = new ProxyClickBlocker($this->session);
		$blocker->setMsisdn($this->msisdn);
		$blocker->setTelcoId($this->telcoId);

		if (($code = $blocker->execute(false)) != ClickBlocker::blk_none) {
			Logger::$logger->info("blocked [{$this->msisdn}] by access via Proxy");

			return $code;
		}

		$blocker = new CPDLimitClickBlocker($this->session);
		$blocker->setMsisdn($this->msisdn);
		$blocker->setTelcoId($this->telcoId);
		$blocker->setLimit(20);

		if (($code = $blocker->execute(false)) != ClickBlocker::blk_none) {
			Logger::$logger->info("blocked [{$this->msisdn}] by CPD");

			return $code;
		}

		$blocker = new BlacklistClickBlocker($this->session);
		$blocker->setMsisdn($this->msisdn);
		$blocker->setTelcoId($this->telcoId);

		if (($code = $blocker->execute(false)) != ClickBlocker::blk_none) {
			Logger::$logger->info("blocked [{$this->msisdn}] by Blacklist");

			return $code;
		}


		$blocker = new SubLimitClickBlocker($this->session);
		$blocker->setMsisdn($this->msisdn);
		$blocker->setTelcoId($this->telcoId);
		$blocker->setLimit(2);

		if (($code = $blocker->execute(false)) != ClickBlocker::blk_none) {
			Logger::$logger->info("blocked [{$this->msisdn}] by Service Limit");

			return $code;
		}

		$blocker = new TryLimitClickBlocker($this->session);
		$blocker->setMsisdn($this->msisdn);
		$blocker->setTelcoId($this->telcoId);
//		$blocker->setLimit(1);
		$blocker->setLimit(10);

		if (($code = $blocker->execute(false)) != ClickBlocker::blk_none) {
			Logger::$logger->info("blocked [{$this->msisdn}] by Service Limit");

			return $code;
		}

		$blocker = new LastSubLimitClickBlocker($this->session);
		$blocker->setMsisdn($this->msisdn);
		$blocker->setTelcoId($this->telcoId);
//		$blocker->setLimit(10);
		$blocker->setLimit(20);

		if (($code = $blocker->execute(false)) != ClickBlocker::blk_none) {
			Logger::$logger->info("blocked [{$this->msisdn}] by Last Sub");

			return $code;
		}

		$blocker = new LastMsisdnLimitClickBlocker($this->session);
		$blocker->setMsisdn($this->msisdn);
		$blocker->setTelcoId($this->telcoId);
#		$blocker->setLimit(10);

		if (($code = $blocker->execute(false)) != ClickBlocker::blk_none) {
			Logger::$logger->info("blocked [{$this->msisdn}] by Last MSISDN");

			return $code;
		}

		$blocker = new HPSLimitClickBlocker($this->session);
		$blocker->setMsisdn($this->msisdn);
		$blocker->setTelcoId($this->telcoId);
		$blocker->setLimit(30);

		if (($code = $blocker->execute(false)) != ClickBlocker::blk_none) {
			Logger::$logger->info("blocked [{$this->msisdn}] by HPS");

			return $code;
		}

		$blocker = new SubscriberCounterLimitClickBlocker($this->session);
		$blocker->setMsisdn($this->msisdn);
		$blocker->setTelcoId($this->telcoId);
		$blocker->setClickId($this->clickId);

		if (($code = $blocker->execute(false)) != ClickBlocker::blk_none) {
			Logger::$logger->info("blocked [{$this->msisdn}] by SLIMIT");
			$this->auxCode = $blocker->getAuxCode();

			return $code;
		}

		$blocker = new AttemptCounterLimitClickBlocker($this->session);
		$blocker->setMsisdn($this->msisdn);
		$blocker->setTelcoId($this->telcoId);
		$blocker->setClickId($this->clickId);

		if (($code = $blocker->execute(false)) != ClickBlocker::blk_none) {
			Logger::$logger->info("blocked [{$this->msisdn}] by ALIMIT");
			$this->auxCode = $blocker->getAuxCode();

			return $code;
		}

		return $code;
	}
}

