<?php

require_once('com/moserv/log/logger.php');
require_once('com/moserv/net/session.php');
require_once('com/moserv/net/url.php');
require_once('com/moserv/wap/wap-media.php');
require_once('com/moserv/wap/wap-sub.php');

class WapAoc {
	private $session;
	private $clickId;
	private $aocId;

	private $affiliateId;
	private $validateCode;
	private $serviceId;
	private $serviceName;
	private $shortcode;
	private $keyword;
	private $ivrCode;
	private $interfaceId;
	private $price;
	private $title;
	private $pageId;

	public function __construct($session) {
		$this->session = $session;

		$this->clickId = 0;
		$this->aocId = 0;
		$this->interfaceId = WapMedia::inf_unknown;
	}

	public function setClickId($clickId) {
		$this->clickId = $clickId;
	}

	public function setAocId($aocId) {
		$this->aocId = $aocId;
	}

	protected function save() {
		$this->aocId = 0;
		$connection = $this->session->getConnection();

		$query = $connection->createQuery('insert into wap.aoc (click_id) values (?)');
		$query->setInt(1, $this->clickId);
		$query->open();

		$this->aocId = $connection->lastId();

		return $this->aocId;
	}

	public function load($t = false) {
		$connection = $this->session->getConnection();

		$query = $connection->createQuery(
<<<sql
			select
				c.click_id,
				f.affiliate_id,
				f.affiliate_code,
				f.validate_code,
				g.campaign_title,
				s.service_id,
				s.service_name,
				t.shortcode,
				t.keyword,
				t.ivr_code,
				i.interface_id,
				o.price,
				p.page_id
			from wap.aoc a
				join wap.click c using (click_id)
				join wap.click_tag t using (click_tag_id)
				join wap.hit h using (hit_id)
				join wap.page p using (page_id)
				join wap.affiliate f using (affiliate_id)
				join wap.campaign g on g.campaign_id = t.campaign_id

				join message_service.service s using (service_id)
				join message_service.incoming_channel i using (service_id)
				join message_service.telco_service t using (service_id, telco_id)
				join message_service.outgoing_channel o using (interface_id, service_id)
			where a.aoc_id = ?
			and i.channel_type_id = ?
			and i.telco_id = ?
			and o.sms_cat_id = ?
			limit 1
sql
		);


//		if ($this->session->getMsisdn() == '66817204478') {
//			echo "{$this->aocId} {$this->session->getTelcoId()}";
//			exit;
//		}



		if (($telcoId = $this->session->getTelcoId()) == WapMedia::telco_unk) {
			$telcoId = ($this->session->getCpId() == '641')? WapMedia::telco_rmv: WapMedia::telco_dtc;
		}


//		Logger::$logger->info("kup check ==> : ".$this->session->getCpId());

		$query->setInt(1, $this->aocId);
//		$query->setInt(2, WapMedia::ctype_wap_sms);
		$query->setInt(2, WapMedia::ctype_wap_aoc);
//		$query->setInt(3, ($this->session->getTelcoId() == WapMedia::telco_unk)? WapMedia::telco_ais: $this->session->getTelcoId());
//		$query->setInt(3, ($this->session->getTelcoId() == WapMedia::telco_unk)? WapMedia::telco_dtc: $this->session->getTelcoId());
		$query->setInt(3, $telcoId);
		# make ais as the prototype of unknown telco (wifi)
//		$query->setInt(3, $this->session->getTelcoId());
		$query->setInt(4, WapMedia::scat_brc);

//		if ($this->session->getMsisdn() == '66817204478') {
//			echo $query->getParsedSql();
//			exit;
//		}

//		if ($this->session->getMsisdn() == '66945285427') {
//			echo $query->getParsedSql();
//			exit;
//		}

		$query->open();

		$rows = $query->getResultArray();


		if (count($rows) > 0) {
			$row = $rows[0];

			$this->clickId		= $row['click_id'];
			$this->affiliateId	= $row['affiliate_id'];
			$this->affiliateCode	= $row['affiliate_code'];
			$this->validateCode	= $row['validate_code'];
			$this->serviceId	= $row['service_id'];
			$this->serviceName	= $row['service_name'];
			$this->shortcode	= $row['shortcode'];
			$this->keyword		= $row['keyword'];
			$this->ivrCode		= $row['ivr_code'];
			$this->interfaceId	= ($this->session->getTelcoId() == WapMedia::telco_unk)? WapMedia::inf_unknown: $row['interface_id'];
//			$this->interfaceId	= $this->session->getTelcoId();
			$this->price		= $row['price'];
			$this->title		= $row['campaign_title'];
			$this->pageId		= $row['page_id'];
		}
	}

	public function redirect() {


		$this->save();
		$this->load();

//		if ($this->pageId == 3735) {
//			echo "serviceid = {$this->serviceId}";
//			exit;
//		}
//
//		if ($this->serviceId == 909 || $this->serviceId == 911) {
//			echo "interfaceid={$this->interfaceId}";
//			exit;
//		}

		Logger::$logger->info("interface: {$this->interfaceId}");

		if (
			$this->interfaceId == WapMedia::inf_ais_cdg ||
			$this->interfaceId == WapMedia::inf_dtc_cpa ||
			$this->interfaceId == WapMedia::inf_tmh_mpt ||
			$this->interfaceId == WapMedia::inf_dtc_sdp #||
#			$this->interfaceId == WapMedia::inf_unknown
		) {
			$this->subscribe();
		}
		else {

			$url = new Url();
			$url->setPath('/aoc.php');
			$url->setParams(
				array(
					'_'	=> 1,
					'_a'	=> $this->aocId
				)
			);

			$url->redirect();
#			echo $url->toString();
		}

		exit;
	}

	public function clickLog() {
		$url = new Url();
		$telco = (WapMedia::$telcos[$this->session->getTelcoId()] == "")? 'nul': WapMedia::$telcos[$this->session->getTelcoId()];
		$affiliateCode = ($this->affiliateCode == '')? 'nul': $this->affiliateCode;
		$msisdn = ($this->session->getMsisdn() == '')? 'unknown': $this->session->getMsisdn();

		$line = sprintf(
			"CLICK T(%s), A(%s), M(%s), P(%05d) - %s%s",
				$telco,
				$affiliateCode,
				substr($msisdn, 0, 4).'-'.substr($msisdn, 4, 3).'-'.substr($msisdn, 7),
				$this->pageId,
				$url->getHost(),
				$url->getPath()
		);

		$this->session->takeLog($line);
	}

	public function subscribe() {

		$url = new Url();
#		$this->clickLog();
		$subscriptor = null;

		switch ($this->interfaceId) {
			case WapMedia::inf_ais_lgc:
				$subscriptor = new LegacyMoSender();
			break;

			case WapMedia::inf_dtc_cpa:
			case WapMedia::inf_dtc_sdp:
#			case WapMedia::inf_unknown:
#				$subscriptor = new SdpMoSender();


				if ($url->getHost() == 'm.kodfin.com') {
					$subscriptor = new SdpConsentAocToken();
				}
				else
					$subscriptor = new SdpConsentAoc();
			break;

			case WapMedia::inf_tmv_css:
				$subscriptor = new TmvMoSender();
			break;

			case WapMedia::inf_tmh_css:
				$subscriptor = new TmhMoSender();
			break;

#			case WapMedia::inf_ais_cdg: $moSender = new CdgWapAoc(); break;
			case WapMedia::inf_ais_cdg:
#				$subscriptor = new CdgWapAocSecure();
				$subscriptor = new CdgWapAocToken();
			break;

			case WapMedia::inf_tmh_mpt:
				$subscriptor = new TmhMptAoc();
			break;

		}

		if ($subscriptor != null) {
			$subscriptor->setSession($this->session);
			$subscriptor->setShortcode($this->shortcode);
			$subscriptor->setIvrCode($this->ivrCode);
			$subscriptor->setValidateCode($this->validateCode);
			$subscriptor->setText($this->keyword);
			$subscriptor->setAffiliateId($this->affiliateId);
			$subscriptor->setClickId($this->clickId);
			$subscriptor->setPageId($this->pageId);
			$subscriptor->setServiceId($this->serviceId);
			$subscriptor->setChannelTypeId(WapMedia::ctype_wap_aoc);

			$code = $subscriptor->execute();
		}

		
	}

	public function getInterfaceId() {
		return $this->interfaceId;
	}

	public function getClickId() {
		return $this->clickId;
	}

	public function getAocId() {
		return $this->aocId;
	}

	public function getServiceId() {
		return $this->serviceId;
	}

	public function getServiceName() {
		return $this->serviceName;
	}

	public function getShortcode() {
		return $this->shortcode;
	}

	public function getIvrCode() {
		return $this->ivrCode;
	}

	public function getValidateCode() {
		return $this->validateCode;
	}

	public function getPrice() {
		return $this->price;
	}

	public function getKeyword() {
		return $this->keyword;
	}

	public function getTitle() {
		return $this->title;
	}
}

?>