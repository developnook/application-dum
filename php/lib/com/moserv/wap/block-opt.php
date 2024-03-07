<?php

require_once('com/moserv/log/logger.php');
require_once('com/moserv/net/session.php');
require_once('com/moserv/net/url.php');
require_once('com/moserv/sql/connection.php');
require_once('com/moserv/wap/wap-media.php');


abstract class OptBlocker {

	const blk_none		= 0x00;
	const blk_black		= 0x01;

#	const blk_climit	= 0x02;
#	const blk_tlimit	= 0x03;
	const blk_temp		= 0x04;
#	const blk_lastsub	= 0x05;
#	const blk_proxy		= 0x06;
#	const blk_slimit	= 0x07;
#	const blk_alimit	= 0x08;
#	const blk_hps		= 0x0a;
#	const blk_cpd		= 0x0b;
#	const blk_last_msisdn	= 0x0c;

	protected $session;
	protected $msisdn;
	protected $telcoId;

	protected $hitId;
	protected $clickId;

	protected $auxCode;

	public function __construct($session) {
		$this->session = $session;
		$this->telcoId = WapMedia::telco_unk;
		$this->auxCode = 0;
	}


	public function setMsisdn($msisdn) {
		$this->msisdn = $msisdn;
	}

	public function setTelcoId($telcoId) {
		$this->telcoId = $telcoId;
	}

	public function setHitId($hitId) {
		$this->hitId = $hitId;
	}

	public function setClickId($clickId) {
		$this->clickId = $clickId;
	}

	protected abstract function doExecute();

	protected function blockLog($code) {
		$url = new Url();
		$telco = (WapMedia::$telcos[$this->session->getTelcoId()] == "")? 'nul': WapMedia::$telcos[$this->session->getTelcoId()];
		$msisdn = ($this->session->getMsisdn() == '')? 'unknown': $this->session->getMsisdn();

		$line = sprintf(
			"H-BLOCK T(%s), M(%s) C(%02d) - %s%s",
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
			insert ignore into wap.block_opt (
				msisdn,
				click_id,
				block_code,
				aux_code
			)
			values (?, ?, ?, ?)
sql
		);

		$query->setInt(1, $this->msisdn);
		$query->setInt(2, $this->clickId);
		$query->setInt(3, $blockCode);
		$query->setInt(4, $auxCode);

		$query->open();
	}


	public function execute($saveRevisit = true) {
		$code = OptBlocker::blk_none;

		if (($code = $this->doExecute()) != OptBlocker::blk_none && $saveRevisit) {
			$this->save($code, $this->auxCode);

			$this->blockLog($code);
		}

		return $code;
	}

	public function getAuxCode() {
		return $this->auxCode;
	}
}

class BlacklistOptBlocker extends OptBlocker {

	protected function doExecute() {

		$connection = $this->session->getConnection();
		
		$query = $connection->createQuery(
<<<sql
			select
				bm.msisdn,
				bia.enabled
			from	wap.block_msisdn bm
			where 	bm.msisdn = ?
				and bm.enabled = 1
sql
		);

		$query->setString(1, $this->msisdn);

		$query->open(false);

		$rows = $query->getResultArray();

		$blocked = (count($rows) > 0);

		return ($blocked)? OptBlocker::blk_black: ClickBlocker::blk_none;
	}
}

class TempOptBlocker extends OptBlocker {

	protected function doExecute() {
#		$url = new Url();

#		return ($url->getHost() == 'm.kangsom.com')? HitBlocker::blk_temp: HitBlocker::blk_none;

#		if (preg_match('/\b(27\.55|49\.237|223\.24)\.\d+\.\d+\b/', $this->session->getRemoteAddress())) { # temporary block for true move h
#			return HitBlocker::blk_temp;
#		}
#		else			return HitBlocker::blk_none;

		return OptBlocker::blk_none;
	}
}


class WapOptBlocker extends OptBlocker {
	protected function doExecute() {

		if (
			empty($this->msisdn)		||
			$this->msisdn == "66945285427"	||	# moserv test phone
			$this->msisdn == "66869743416"	||	# man dtac
			$this->msisdn == "66922637316"	||	# man ais
#			$this->msisdn == "66817204478"	||	# mo ais
			$this->msisdn == "66624873810"	||	# moserv ais test phone
			$this->msisdn == "66622944908"	||	# ae ais
			$this->msisdn == "66871276524"	||	# p alex ais
			$this->msisdn == "66954911485"	||	# moserv test phone (true)
			$this->msisdn == "66989234406"	||	# moserv test phone#1 (ais moserv-owned)
			$this->msisdn == "66818312138"	||	# moserv test phone#2 (ais ais-owned)
			$this->msisdn == "66815120524"	||	# moserv test phone#3 (ais ais-owned)
			$this->msisdn == "66818333336"	||	# moserv test phone#4 (ais ais-owned)
			$this->msisdn == "66899201960"	||	# AIS test phone (ais)
			$this->msisdn == "66865630349"	||	# truemove owner (true)
			$this->msisdn == "66629794094"	||	# kib nong kai
			$this->msisdn == "66894851777"	||	# k.oh dtac
			$this->msisdn == "66994541442"	||	# k.da true
			$this->msisdn == '66955646231'	||	# Ae Postpaid
			$this->msisdn == '66642685530'	||	# Ae True Postpaid
			$this->msisdn == '66957109758'  ||      // tong True Prepaid
			$this->msisdn == '66858434402'	||	# Test True
			$this->msisdn == "66902904079"	||	# test no. from true move h
			$this->msisdn == "66639912636"	||	# test no. from true move h
			$this->msisdn == '66866299868'  ||      # test no. from true move h
                        $this->msisdn == '66865760020'  ||      # test no. from true move h
			$this->msisdn == "66868164860"		# moserv test phone #5
		) {
			return OptBlocker::blk_none;
		}

		$code = OptBlocker::blk_none;

		$blocker = new BlacklistOptBlocker($this->session);
		$blocker->setMsisdn($this->msisdn);
		$blocker->setTelcoId($this->telcoId);
		if (($code = $blocker->execute(false)) != OptBlocker::blk_none) {
			Logger::$logger->info("blocked [{$this->msisdn}] by Opt");

			return $code;
		}

#		$blocker = new TempOptBlocker($this->session);
#		$blocker->setMsisdn($this->msisdn);
#		$blocker->setTelcoId($this->telcoId);
#		if (($code = $blocker->execute(false)) != OptBlocker::blk_none) {
#			Logger::$logger->info("blocked [{$this->msisdn}] by Temp");
#
#			return $code;
#		}
		
		return $code;
	}
}

