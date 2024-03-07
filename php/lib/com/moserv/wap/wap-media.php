<?php

require_once('com/moserv/log/logger.php');
require_once('com/moserv/net/session.php');
require_once('com/moserv/net/http.php');
require_once('com/moserv/net/url.php');
require_once('com/moserv/sql/connection.php');
require_once('com/moserv/util/web.php');
require_once('com/moserv/wap/wap-tag.php');
require_once('com/moserv/wap/wap-sub.php');
require_once('com/moserv/wap/block-click.php');
require_once('com/moserv/wap/block-hit.php');
require_once('com/moserv/wap/pixee.php');
require_once('com/moserv/xml/model.php');

class ClickTag {

	private $hitId;
	private $clickTagId;

	private $text;
	private $dirText;
	private $smsText;
	private $ivrText;
	private $aocText;

	private $shortcode;
	private $keyword;
	private $ivrCode;
	private $channelTypeId;

	private $telcoId;

	private $url;

	public function __construct() {
		$this->text = 'Click';
	}

	public function setHitId($hitId) {
		$this->hitId = $hitId;
	}

	public function setClickTagId($clickTagId) {
		$this->clickTagId = $clickTagId;
	}

	public function setText($text) {
		$this->text = $text;
	}

	public function setDirText($dirText) {
		$this->dirText = $dirText;
	}

	
	public function setSmsText($smsText) {
		$this->smsText = $smsText;
	}

	public function setIvrText($ivrText) {
		$this->ivrText = $ivrText;
	}

	public function setAocText($aocText) {
		$this->aocText = $aocText;
	}


	public function setShortcode($shortcode) {
		$this->shortcode = $shortcode;
	}

	public function setKeyword($keyword) {
		$this->keyword = $keyword;
	}

	public function setIvrCode($ivrCode) {
		$this->ivrCode = $ivrCode;
	}

	public function setChannelTypeId($channelTypeId) {
		$this->channelTypeId = $channelTypeId;
	}

	public function setUrl($url) {
		$this->url = $url;
	}

	public function setTelcoId($telcoId) {
		$this->telcoId = $telcoId;
	}

	public function getText() {
		return $this->text;
	}

	public function getFullUrl() {
		global $_REQUEST;

		$url = new Url($this->url);
		$url->setParam('h', $this->hitId);
		$url->setParam('t', $this->clickTagId);
		$url->setParam('_', 1);

		if (array_key_exists('_i', $_REQUEST) && !empty($_REQUEST['_i'])) {
			$url->setParam('_i', $_REQUEST['_i']);
		}

		return $url->toString();
	}

	public function getTags() {
		$ttt = 'Click';

		switch ($this->channelTypeId) {
			case WapMedia::ctype_wap_dir:
				$text = $this->dirText;
			break;

			case WapMedia::ctype_sms:
			case WapMedia::ctype_wap_sms:
				$text = $this->smsText;
			break;

			case WapMedia::ctype_ivr:
			case WapMedia::ctype_wap_ivr:
				$text = $this->ivrText;
			break;

			case WapMedia::ctype_wap_aoc:
				$text = $this->aocText;
			break;
		}

		Logger::$logger->info("text => $ttt $this->channelTypeId " . (6 == $this->channelTypeId));

		$search = array('{{shortcode}}', '{{keyword}}', '{{ivrcode}}');
		$replace = array($this->shortcode, $this->keyword, $this->ivrCode);

		$text = str_replace($search, $replace, $text);

		$tokens = preg_split('/(\[\[[^\]]+\]\])/', $text, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);

		
		Logger::$logger->info("token array " . print_r($tokens, true));

		$tags = new XmlTagList();

		foreach ($tokens as $token) {
			$tag = new CustomTag();

			Logger::$logger->info("token $token");

			if (preg_match('/^\[\[([^\]]+)\]\]$/', $token, $group)) {
				$tag->setName('a');
				$tag->setAttr('proxy', 'no');
				$tag->setAttr('href', $this->getFullUrl());

				$line = $group[1];
				Logger::$logger->info("token link $line");
			}
			else {
				$tag->setName('text');
				$line = $token;
				Logger::$logger->info("token nolink");
			}

			$tag->setAttr('br', '0');

			$tag->addTag(new TextTag($line));

			$tags->addTag($tag);
		}

		return $tags;
	}

	public function toString($newLine = true) {
		$tags = $this->getTags();
#		if ($tags->count() == 0) {
#			$tags->addTag(new TextTag(''));
#		}

#		if ($newLine)
#			$tags->getTag($tags->count() - 1)->setAttr('br', '1');

		return $tags->toString();
	}

	public function getClickXmlParam() {
		global $_REQUEST;

#		$wapMedia = $this->wapMedia;

		$hitId = $this->hitId;
		$clickTagId = $this->clickTagId;

		$list = array(
			'_'	=> 1,
			'h'	=> $hitId,
			't'	=> $clickTagId
		);

		$params = array();

		foreach ($list as $key => $value) {
			$params[] = "$key=$value";
		}

		return implode('&amp;', $params);
	}

	public function getShortcode() {
		return $this->shortcode;
	}

	public function getKeyword() {
		return $this->keyword;
	}

	public function getIvrCode() {
		return $this->ivrCode;
	}
}



class WapMedia  {
	const ctype_sms		= 1;
	const ctype_ivr		= 2;
	const ctype_wap_dir	= 3;
	const ctype_wap_sms	= 4;
	const ctype_wap_ivr	= 5;
	const ctype_wap_aoc	= 6;

	const telco_unk		= -1;
	const telco_non		= 0;
	const telco_mos		= 1;
	const telco_ais		= 2;
	const telco_dtc		= 3;
	const telco_tmv		= 4;
	const telco_rmv		= 5;

	const inf_unknown	= -1;
	const inf_mos_css	= 1;
	const inf_ais_cdg	= 2;
	const inf_ais_lgc	= 3;
	const inf_dtc_cpa	= 4;
	const inf_tmv_css	= 5;
	const inf_tmh_css	= 6;
	const inf_dtc_sdp	= 7;
	const inf_tmh_mpt	= 15;

	const cg_clip		= 1;
	const cg_wall		= 2;
	const cg_quiz		= 3;
	const cg_horo		= 4;
	const cg_info		= 5;

	const scat_unk		= -1;           
	const scat_wel		= 1;
	const scat_1st		= 2;     	
	const scat_brc		= 3; 
	const scat_ret		= 4;
	const scat_xce		= 5;
	const scat_wrn		= 6;   
	const scat_rec  	= 7;

	const default_page = 118;

	public static $instCount = 0;

	public static $telcos = array(
		-1 => 'nul',
		0 => 'nul',
		1 => 'mos',
		2 => 'ais',
		3 => 'dtc',
		4 => 'tmv',
		5 => 'rmv'
	);

	protected $session;
	protected $connection;
	protected $pageId;
	protected $landingPage;
	protected $closeButton;
	protected $affiliateId;
	protected $affiliateCode;
	protected $affiliateName;
	protected $sexyAllow;
	protected $campaignId;
	protected $campaignName;
	protected $clickTag;
	protected $priceTag;
#	protected $serviceId;
	protected $enabled;
	protected $isSexy;
	protected $campaignTitle;
	protected $campaignGroupId;
	protected $campaignGroupName;

#	protected $passThrough;


	protected $applyText;
	protected $channelTypeId;
	protected $pattern;
	protected $shortcode;
	protected $cpId;
	protected $keyword;
	protected $clickUrl;


	protected $clickTags;
	protected $crossSells;


	public function __construct($session, $pageId = WapMedia::default_page) {
		global $_REQUEST;
		global $price;

		self::$instCount++;

		if (self::$instCount == 1) {
			if (!array_key_exists('p', $_REQUEST)) {
				$_REQUEST['p'] = '';
			}

			if (empty($price)) {
				$price = '';
			}
		}

		$this->session = $session;
		$this->connection = $this->session->getConnection();
		$this->pageId = $pageId;
		$this->isSexy = false;
		$this->campaignId = -1;
		$this->clickTags = array();
	}

	public function setClickUrl($clickUrl) {
		$this->clickUrl = $clickUrl;
	}

	public function getMainClickUrl() {
		if (count($this->clickTags) > 0) {
			$mainTag = $this->clickTags[0];
			return $mainTag->getFullUrl();
		}
		else
			return null;
	}

	public function hitLog() {
		$telco = (WapMedia::$telcos[$this->session->getTelcoId()] == "")? 'nul': WapMedia::$telcos[$this->session->getTelcoId()];
		$affiliateCode = ($this->affiliateCode == '')? 'nul': $this->affiliateCode;
		$msisdn = ($this->session->getMsisdn() == '')? '00000000000': $this->session->getMsisdn();
		$url = new Url();

		$line = sprintf(
			"HIT T(%s), A(%s), M(%s), P(%05d) - %s%s",
				$telco,
				$affiliateCode,
				substr($msisdn, 0, 4).'-'.substr($msisdn, 4, 3).'-'.substr($msisdn, 7),
				$url->getParam('p'),
				$url->getHost(),
				$url->getPath()
		);

		$this->session->takeLog($line);
	}

	public function load() {

		$query = $this->connection->createQuery(
<<<sql
			select
				p.page_id,
				o.landing_page || a.landing_page || p.landing_page as landing_page,
				o.close_button || a.close_button || p.close_button as close_button,
				a.affiliate_id,
				a.affiliate_code,
				a.affiliate_name,
				a.sexy_allow,
				c.campaign_id,
				c.campaign_name,
				c.click_tag,
				c.price_tag,
				c.campaign_title,

				/******************
				* c.shortcode,
				* substr(c.shortcode, 2, 3) as cp_id,
				*  c.service_id,
				*******************/

				s.shortcode,
				substr(s.shortcode, 2, 3) as cp_id,

				o.enabled && a.enabled && c.enabled && p.enabled as enabled,
				c.sexy,
				g.campaign_group_id,
				g.campaign_group_name
			from wap.page p
				join wap.affiliate a using (affiliate_id)
				join wap.campaign c using (campaign_id)
				join wap.campaign_group g using (campaign_group_id)

				left join (select landing_page, close_button, telco_id, enabled from message_service.telco where telco_id = ?) o on 1 = 1
				left join wap.click_tag t on t.click_tag_id = (
					select
						click_tag_id
					from wap.click_tag
					where campaign_id = c.campaign_id
					limit 1
				)
				left join message_service.telco_service s using (telco_id, service_id)
			where p.page_id = ?
sql
		);

		$pageId = $this->pageId;

		switch ($this->pageId) {
			case 2748: $pageId = 2792; break;
			case 2734: $pageId = 2770; break;
			case 2757: $pageId = 3793; break;

			case 2763:
			case 2783: 
			case 3803:
// nook			case 3833:
				$pageId = 3877;
			break; // adlp

			case 3869:
				$pageId = 3893;
			break; // yomi

			case 3875:
				$pageId = 3892;
			break; // xsp

			case 3829:
			case 3843:
				$pageId = 3894;
			break; // hiv

			case 3862:
				$pageId = 3902;
			break; // lnm
				

			case 2785: $pageId = 3817; break; // sp2

			case 2758:
			case 2782:
			case 3812:
// nook			case 3849:
				$pageId = 3882;
			break; // mbp

			case 2739:
			case 2768: $pageId = 3816; break; // him

// -- ALEX Added 2020/04/16
			case 2775: $pageId = 3813; break; // mbm
			case 2777: $pageId = 3823; break; // mbt
			case 2779: $pageId = 3814; break; // jwm
			case 2774: $pageId = 3811; break; // asp
			case 2781: $pageId = 3798; break; // hwd
			case 2788: $pageId = 3824; break; // unb
			case 2786: $pageId = 3825; break; // ymb

			case 3793:
			case 3822:
			case 3871:
				$pageId = 3899; break;
			break; // mkt
			
// -- ALEX Added 2020/08/11
			case 3806: $pageId = 3842; break; // mul
			case 3822: $pageId = 3871; break; // mkt
			
// 2020-05-26 Move @Service 915
//			case 3810: $pageId = 3839; break; // mdpk
//			case 3812: $pageId = 3849; break; // mbp
// -- ALEX END ADD

// NOOK Added 2021/01/09 | Service ID : 917 swop to Service ID : 918
			case 3955: $pageId = 3981; break; // sp
			case 3952: $pageId = 3982; break; // moj
			case 3965: $pageId = 3983; break; // agm
			case 3948: $pageId = 3984; break; // adlp
			case 3973: $pageId = 3985; break; // arp
			case 3980: $pageId = 3986; break; // snz
			case 3976: $pageId = 3987; break; // bot
			case 3953: $pageId = 3988; break; // acr
			case 3966: $pageId = 3989; break; // mul
			case 3979: $pageId = 4009; break; // adlp2

// NOOK Added 2021/03/11 | Service ID : 918 swop to Service ID : 919
			case 3984: $pageId = 4025; break; // adlp
			case 3981: $pageId = 4022; break; // sp
			case 4008: $pageId = 4047; break; // ifn
			case 3986: $pageId = 4027; break; // snz
			case 3995: $pageId = 4035; break; // wit
			case 3985: $pageId = 4026; break; // arp

			case 3982: $pageId = 4023; break; // moj
			case 3983: $pageId = 4024; break; // agm
			case 4021: $pageId = 4059; break; // mot
			case 4002: $pageId = 4042; break; // mbp
			case 4004: $pageId = 4043; break; // hlm
			case 3987: $pageId = 4028; break; // bot
			case 3989: $pageId = 4030; break; // mul
			case 3988: $pageId = 4029; break; // acr
// Nook END ADD
		}


		$query->setInt(1, $this->session->getTelcoId());
//		$query->setInt(2, $this->pageId);
		$query->setInt(2, $pageId);

//		Logger::$logger->info('page id ===> '. $this->pageId);


		$query->open(false);

		Logger::$logger->info($query->getParsedSql());


		$result = $query->getResultArray();

		Logger::$logger->info(count($result));

		if (count($result) > 0) {
			$record = $result[0];

#			$this->passThrough		= $record['pass_through'];

#			$this->landingPage		= $record['landing_page'];
#			$this->landingPage		= Click::hardcode($this->session->getHitId(), $record['landing_page']);
			$this->landingPage		= Click::hardcode(
				$this->session->getTelcoId(),
				$this->session->getHitId(),
				$record['landing_page']
			);
			$this->closeButton		= $record['close_button'];
			$this->affiliateId		= $record['affiliate_id'];
			$this->affiliateCode		= $record['affiliate_code'];
			$this->affiliateName		= $record['affiliate_name'];
			$this->sexyAllow		= ($record['sexy_allow'] != 0);
			$this->campaignId		= $record['campaign_id'];
			$this->campaignName		= $record['campaign_name'];
			$this->clickTag			= $record['click_tag'];
			$this->priceTag			= $record['price_tag'];
			$this->shortcode		= $record['shortcode'];
			$this->cpId			= $record['cp_id'];
#			$this->serviceId		= $record['service_id'];
			$this->enabled			= $record['enabled'];
			$this->isSexy			= ($record['sexy'] != 0);
			$this->campaignTitle		= $record['campaign_title'];
			$this->campaignGroupId		= $record['campaign_group_id'];
			$this->campaignGroupName	= $record['campaign_group_name'];

			$this->hitLog();


#			Logger::$logger->info("mtitle = {$this->campaignTitle}");

			if (self::$instCount == 1) {

				$this->checkHitBlocker();

##				$addresses = preg_split('/\s*,\s*/', $this->session->getRemoteAddress());
##
##				if (count($addresses) > 1) {
##					$this->gotoRedirect("http://www.google.com");
##					exit;
##				}



##				if (
##					$this->session->getTelcoId() == WapMedia::telco_ais &&
##					$this->session->getMsisdn() != '66817204478' &&
##					$this->session->getMsisdn() != '66622944908'
##				) {
##					$this->gotoFileNotFound();
##				}

##				if ($this->session->getTelcoId() == WapMedia::telco_dtc && $this->session->getMsisdn() != '66945285427') {
##				if ($this->session->getTelcoId() == WapMedia::telco_dtc) {
##					$this->gotoFileNotFound();
##				}


#				if ($this->enabled == 0 && $this->isBypassAble()) {
#				if ($this->enabled == 0 && $this->session->getMsisdn() != "66814762091" && $this->isBypassAble()) {
				if ($this->enabled == 0 && $this->session->getMsisdn() != "66617058447" && $this->isBypassAble()) {
					Logger::$logger->info("404: $this->pageId : $this->enabled");

					$this->gotoFileNotFound();
				}



				$this->forceRedirect();


#				if ($this->enabled == 1 && $this->passThrough != 0) {
#					$url = new Url();

#					$cpId = substr($this->shortcode, 1, 3);
#					$host = Web::getCfgVar("cp{$cpId}host");
#					$path = str_replace('.xml.', '.', $url->getPath());
#
#					global $_SERVER;
#					echo $_SERVER['REQUEST_URI'];
#					Logger::$logger->info("tttt: ". $path);
#					exit;
#
#					$url->setHost($host);
#					$url->setPath($path);
#					$url->setParams(array());
#
#					$this->gotoRedirect($url->toString());
#				}

				$tags = $this->loadClickTags(); # must be outside of below if

#				echo "test enabled={$this->enabled}, landingPage={$this->landingPage}";
#				exit;

				if ($this->enabled == 1 && $this->landingPage == 0 && $this->isBypassAble()) {
					$index = array_rand($tags);
					$tag = $tags[$index];

					$this->gotoRedirect($tag->getFullUrl());
//					$this->gotoRedirect($tag->getFullUrl(), ($this->session->getTelcoId() == 2)? 10: 0);
				}
			}
			else {
				$this->loadClickTags();
			}
		}
	}

	protected function checkHitBlocker() {

		$blocker = new WapHitBlocker($this->session);
		$blocker->setMsisdn($this->session->getMsisdn());
		$blocker->setTelcoId($this->session->getTelcoId());
		$blocker->setHitId($this->session->getHitId());



		if (($code = $blocker->execute()) != 0) {
//mo			$pixee = new Pixee($this->session);
//mo			$pixee->setAffiliateId($this->affiliateId);
//mo			list($pixeeId, $pixeeFiringId, $url) = $pixee->execute(false);

			$this->gotoRedirect($url);

			exit;
		}
	}


	protected function forceRedirect() {
			global $_REQUEST;

			if (array_key_exists('_f', $_REQUEST) && $_REQUEST['_f'] == 1) {
				return;
			}

			if ($this->pageId == 1002) { ### dangerous HARDCODED ###
				$this->gotoHardRedirect(1199, 'm.kodfin.com');
			}

			if ($this->pageId == 1143) { ### dangerous HARDCODED ###
				$this->gotoHardRedirect(1120, 'm.kodfin.com');
			}

			if ($this->pageId == 973) { ### dangerous HARDCODED ###
				$this->gotoHardRedirect(1222, 'm.kodfin.com');
			}

			if ($this->pageId == 1079) { ### dangerous HARDCODED ###
				$this->gotoHardRedirect(1199, 'm.kodfin.com');
			}

			if ($this->pageId == 1135 && $this->session->getTelcoId() == WapMedia::telco_dtc) { ### dangerous HARDCODED ###
				$this->gotoHardRedirect(1233, 'm.kangped.com');
			}
	}

	public function isBypassAble() {
		$url = new Url();
		$isDlPage = preg_match('%^http://m\.(sabver|kangped|kodfin|yumyim|kangsom|kodzed)\.com(:[0-9]+)?/dl/%i', $url->toString());
		$isThkPage = preg_match('%^http://m\.(sabver|kangped|kodfin|yumyim|kangsom|kodzed)\.com(:[0-9]+)?.*/thanks(\.xml)?\.php%i', $url->toString());
		$isHardcoded = preg_match('%^http://m\.(sabver|kangped|kodfin|yumyim|kangsom|kodzed)\.com(:[0-9]+)?.*/ais-wap/%i', $url->toString());

		return (!$isDlPage && !$isThkPage && !$isHardcoded);
	}

	public function gotoFileNotFound() {
		$url = new Url();

		header('HTTP/1.0 404 Not Found');
		header('Status: 404 Not Found');

		echo "OK";

#		echo "File Not Found: " . $url->toString();

		exit;
	}

	public function gotoRedirect($url, $delay = 0) {

		$xurl = htmlspecialchars($url);
		Logger::$logger->info("gotoRedirect to url => {$url}");
		echo "<?xml version=\"1.0\"?>\n<hawhaw><deck title=\"Redirect\" redirection=\"{$delay}; URL={$xurl};proxy=no\" /></hawhaw>";

		exit;
	}

	public function gotoHardRedirect($pageId, $host) {
		global $_REQUEST;

#		$host = Web::getCfgVar("cp{$this->cpId}host");
#		echo "{$this->cpId}yes {$host}";
#		exit;

		$curl = new Url($_REQUEST['code']);
#		$curl->setHost('m.kodfin.com');
		$curl->setHost($host);
		$curl->setParam('p', $pageId);
		$curl->setParam('_', null);
		$curl->setParam('_i', null);

		$target = str_replace('.xml.', '.', $curl->toString());

		$this->gotoRedirect($target);
	}

	protected function loadClickTags() {
/*
		$query = $this->connection->createQuery('
			select
				click_tag_id,
				service_id,
				text,
				direct_text,
				sms_text,
				ivr_text
			from click_tag
			where campaign_id = ?
			order by rank
		');

		$query->setInt(1, $this->campaignId);
		$query->open();

		$result = $query->getResultArray();
		$this->clickTags = array();

		for ($index = 0; $index < count($result); $index++) {
			$record = $result[$index];

			$this->clickTags[] = $service = new ClickTag($this);

			$service->setClickTagId($record['click_tag_id']);
			$service->setServiceId($record['service_id']);
			$service->setText($record['text']);
			$service->setDirectText($record['direct_text']);
			$service->setSmsText($record['sms_text']);
			$service->setIvrText($record['ivr_text']);
		}
*/
		$query = $this->connection->createQuery(
<<<sql
			select
				t.click_tag_id,
				t.service_id,

				t.text,
				t.dir_text,
				t.sms_text,
				t.ivr_text,
				t.aoc_text,

				v.shortcode,
				v.keyword,
				v.ivr_code
			from wap.click_tag t
				join message_service.service s using (service_id)
 				join message_service.telco_service v using (service_id)
			where t.campaign_id = ?
			and v.telco_id = ?
			order by t.rank
sql
		);


		$telcoId = $this->session->getTelcoId();

#		Logger::$logger->info("kup: $telcoId");

		$query->setInt(1, $this->campaignId);
#		$query->setInt(2, ($telcoId == -1)? 2: $telcoId);
		$query->setInt(2, $telcoId);
		$query->open(false);

		$result = $query->getResultArray();

#		if (count($result) == 0) {
#			Logger::$logger->info("NOT FOUND: $this->campaignId");
#		}


		$this->clickTags = array();
		$selector = new ChannelSelector($this->session);

		for ($index = 0; $index < count($result); $index++) {
			$record = $result[$index];

			$selector->setServiceId($record['service_id']);
			$selector->setIvrCode($record['ivr_code']);

			$selector->execute();

			$clickTag = new ClickTag();

			$clickTag->setHitId($this->session->getHitId());
			$clickTag->setClickTagId($record['click_tag_id']);
			$clickTag->setUrl($this->clickUrl);

			$clickTag->setText($record['text']); # temporary case

			$clickTag->setDirText($record['dir_text']);
			$clickTag->setSmsText($record['sms_text']);
			$clickTag->setIvrText($record['ivr_text']);
			$clickTag->setAocText($record['aoc_text']);

			$clickTag->setShortcode($record['shortcode']);
			$clickTag->setKeyword($record['keyword']);
			$clickTag->setIvrCode($record['ivr_code']);

			$clickTag->setChannelTypeId($selector->getChannelTypeId());


			$clickTag->setTelcoId($this->session->getTelcoId());

			$this->clickTags[] = $clickTag;
		}

		return $this->clickTags;
	}

	protected function getCrossSellsQuery($crossSellCount = 5, $shortcode = null) {
		$query = null;

		switch ($this->campaignGroupId) {
			case WapMedia::cg_wall:
			case WapMedia::cg_clip:
			case WapMedia::cg_horo:
				if ($shortcode != null || substr($this->shortcode, 0, 4) === '4707') {

					$query = $this->connection->createQuery(
<<<sql
						select
							p.page_id
						from page p
							join campaign c using (campaign_id)
							join affiliate a using (affiliate_id)
						where p.affiliate_id = ?
						and (c.campaign_group_id = 1 or c.campaign_group_id = 2 or c.campaign_group_id = 4)
						and c.shortcode = ?
						and c.enabled = 1
						and p.campaign_id <> ?
						and (a.sexy_allow <> 0 or c.sexy = 0)
						order by rand()
						limit ?
sql
					);

					$query->setInt(1, $this->affiliateId);
					$query->setString(2, ($shortcode == null)? $this->shortcode: $shortcode);
					$query->setInt(3, $this->campaignId);
					$query->setInt(4, $crossSellCount);
				}
				else {
					$query = $this->connection->createQuery(
<<<sql
						select
							p.page_id
						from page p
							join campaign c using (campaign_id)
							join affiliate a using (affiliate_id)
						where p.affiliate_id = ?
						and c.campaign_group_id = ?
						and substr(c.shortcode, 1, 4) = substr(?, 1, 4)
						and c.enabled = 1
						and p.campaign_id <> ?
						and (a.sexy_allow <> 0 or c.sexy = 0)
						order by rand()
						limit ?
sql
					);

					$query->setInt(1, $this->affiliateId);
					$query->setInt(2, $this->campaignGroupId);
					$query->setString(3, $this->shortcode);
					$query->setInt(4, $this->campaignId);
					$query->setInt(5, $crossSellCount);
				}
			break;

			case WapMedia::cg_info:
				$query = $this->connection->createQuery(
<<<sql
					select
						p.page_id
					from page p
						join campaign c using (campaign_id)
						join affiliate a using (affiliate_id)
					where p.affiliate_id = ?
					and (c.campaign_group_id = 5)
					and c.shortcode = ?
					and c.enabled = 1
					and p.campaign_id <> ?
					and (a.sexy_allow <> 0 or c.sexy = 0)
					order by rand()
					limit ?
sql
				);

				$query->setInt(1, $this->affiliateId);
				$query->setString(2, ($shortcode == null)? $this->shortcode: $shortcode);
				$query->setInt(3, $this->campaignId);
				$query->setInt(4, $crossSellCount);
			break;

			case WapMedia::cg_quiz:
				$query = $this->connection->createQuery(
<<<sql
					(
						select
							p.page_id
						from page p
							join campaign c using (campaign_id)
							join affiliate a using (affiliate_id)
						where p.affiliate_id = ?
						and c.campaign_group_id = 3
						and c.enabled = 1
						and p.campaign_id <> ?
						and (a.sexy_allow <> 0 or c.sexy = 0)
						and substr(c.shortcode, 1, 4) = substr(?, 1, 4)
						order by rand()
						limit ?
					)

					union all

					(
						select
							p.page_id
						from page p
							join campaign c using (campaign_id)
							join affiliate a using (affiliate_id)
						where p.affiliate_id = ?
						and c.campaign_group_id between 1 and 2
						and c.enabled = 1
						and p.campaign_id <> ?
						and (a.sexy_allow <> 0 or c.sexy = 0)
						and substr(c.shortcode, 1, 4) = substr(?, 1, 4)
						order by rand()
						limit ?
					)
sql
				);

				$query->setInt(1, $this->affiliateId);
				$query->setInt(2, $this->campaignId);
				$query->setInt(3, $crossSellCount);
				$query->setInt(4, 1); # limit only 1
				$query->setInt(5, $this->affiliateId);
				$query->setInt(6, $this->campaignId);
				$query->setInt(7, $crossSellCount);
				$query->setInt(8, max(0, $crossSellCount - 1));
			break;
		}

		return $query;
	}


	public function loadCrossSells($crossSellCount = 5, $shortcode = null) {
		$this->crossSells = array();

		if (($query = $this->getCrossSellsQuery($crossSellCount, $shortcode)) != null) {
			$query->open();

			$result = $query->getResultArray();


			for ($index = 0; $index < count($result); $index++) {
				$record = $result[$index];

				$crossSell = new WapMedia($this->session, $record['page_id']);

				$crossSell->setClickUrl($this->clickUrl);

				 Logger::$logger->info("page_id = {$record['page_id']}");

				$crossSell->load();

				$this->crossSells[] = $crossSell;
			}
		}

		return $this->crossSells;
	}

	public function getSession() {
		return $this->session;
	}

	public function getCampaignName() {
		return $this->campaignName;
	}

	public function getCampaignTitle() {
		return $this->campaignTitle;
	}

	public function getCampaignGroupName() {
		return $this->campaignGroupName;
	}

	public function getClickTags() {
		return $this->clickTags;
	}

	public function getCrossSells() {
		return $this->crossSells;
	}

	public function getBigImage() {
		global $_SERVER;

		$spec = sprintf('/images/wap/b%06d-%s.gif', $this->campaignId, $this->affiliateName);
		$common = sprintf('/images/wap/b%06d.gif', $this->campaignId);

#		return (file_exists(WapMedia::htdocs.$spec))? $spec: $common;
		return (file_exists("{$_SERVER['DOCUMENT_ROOT']}$spec"))? $spec: $common;
	}

	public function getSmallImage() {
		global $_SERVER;

		$spec = sprintf('/images/wap/s%06d-%s.gif', $this->campaignId, $this->affiliateName);
		$common = sprintf('/images/wap/s%06d.gif', $this->campaignId);

#		return (file_exists(WapMedia::htdocs.$spec))? $spec: $common;
		return (file_exists("{$_SERVER['DOCUMENT_ROOT']}$spec"))? $spec: $common;
	}


	public function botTag() {
		$hitId = $this->session->getHitId();
		$info = (array_key_exists('_i', $_REQUEST))? $_REQUEST['_i']: '';
		
		$html = htmlspecialchars(
<<<html
			<style>
				a#bb { display: none; }
			</style>
			<a id="bb" href="/bb.exe.php?h={$hitId}&_i={$info}">bb</a>
html
		);

		return
<<<xml
			<raw markup_language="html">{$html}</raw>
xml
		;
	}

	public function rawTag() {
		global $_REQUEST;


		if ($this->closeButton == 0) {
			return '';
		}
		else {
			$url = new Url();
			$host = $url->getHost();

			$hitId = $this->session->getHitId();
			$info = (array_key_exists('_i', $_REQUEST))? $_REQUEST['_i']: '';

			$html = htmlspecialchars(
<<<html
				<style>

					a#x::before {
						position: absolute;
						content: "";
						top: 14px;
						left: 5px;
						width: 20px;
						height: 2px;
						transform: rotate(45deg);
						background-color: rgba(255, 255, 255, 0.5);
						border-radius: 3px;
						transition: background-color 0.3s;
					}

					a#x::after {
						position: absolute;
						content: "";
						top: 14px;
						left: 5px;
						width: 20px;
						height: 2px;
						transform: rotate(-45deg);
						background-color: rgba(255, 255, 255, 0.5);
						border-radius: 3px;
						transition: background-color 0.3s;
					}

					a#x, a#x:visited {
						position: fixed;
						border-radius: 15px;
						right: 10px;
						top: 10px;
						width: 30px;
						height: 30px;
						background-color: rgba(200,200,200,0.3);
						border: none;
						z-index: 5000;
						transition: background-color 0.3s;
						cursor: pointer;
					}

					a#x:hover {
						background-color: red;

					}

					a#x:hover::before, a#x:hover::after {
						background-color: white;
					}

				</style>
				<a id="x" href="/close.exe.php?h={$hitId}&_i={$info}&_=1"><div /></a>

				<style id="antiClickjack">body{display:none !important;}</style>
				<script type="text/javascript">

					let url = "http://{$host}/process/click-jacking.exe.php?_h={$hitId}";

					if (self === top) {
						let antiClickjack = document.getElementById("antiClickjack");
						antiClickjack.parentNode.removeChild(antiClickjack);
					}
					else {
						
						let xhttp = new XMLHttpRequest();
						
						xhttp.onreadystatechange = function() {
							if (this.readyState == 4 && this.status == 200) {

							}
						};

						xhttp.open("GET", url ,true);
						xhttp.send();

						top.location = self.location;
					}
				</script>
html
			);

			return
<<<xml
				<raw markup_language="html">{$html}</raw>
xml
			;
		}
#
#<<<xml
#		<raw markup_language="html">
#			&lt;style&gt;
#
#				div#x::before {
#					position: absolute;
#					content: &quot;&quot;;
#					top: 13px;
#					left: 5px;
#					width: 20px;
#					height: 4px;
#					transform: rotate(45deg);
#					background-color: white;
#				}
#
#				div#x::after {
#					position: absolute;
#					content: &quot;&quot;;
#					top: 13px;
#					left: 5px;
#					width: 20px;
#					height: 4px;
#					transform: rotate(-45deg);
#					background-color: white;
#				}
#
#				div#x {
#					position: fixed;
#					border-radius: 15px;
#					right: 10px;
#					width: 30px;
#					height: 30px;
#					background-color: rgba(200,200,200,10);
#					border: none;
#					z-index: 5000;
#					transition: background-color 0.3s;
#					cursor: pointer;
#				}
#
#				div#x:hover {
#					background-color: red;
#
#				}
#
#			&lt;/style&gt;
#				&lt;div id=&quot;x&quot; onclick=&quot;window.close();&quot; /&gt;
#		</raw>
#
#xml
		;
	}

	public function getApplyText() {
		return $this->applyText;
	}

	public function getChannelTypeId() {
		return $this->channelTypeId;
	}

	public function getPattern() {
		return $this->pattern;
	}
/*
	public function getShortcode() {
		return $this->shortcode;
	}

	public function getKeyword() {
		return $this->keyword;
	}
*/
	public function getPageId() {
		return $this->pageId;
	}

	public function getCampaignId() {
		return $this->campaignId;
	}

	public function getClickTag() {
		return $this->clickTag;
	}

	public function getPriceTag() {
		return $this->priceTag;
	}
}


class ChannelSelector {

	private $session;
	private $connection;

	private $serviceId;
	private $ivrCode;

	private $interfaceId;
	private $incomingChannelId;
	private $channelTypeId;

	public function __construct($session) {
		$this->session = $session;
		$this->connection = $session->getConnection();

		$this->serviceId = 0;
		$this->telcoId = 0;
		$this->ivrCode = null;
	}

	public function setServiceId($serviceId) {
		$this->serviceId = $serviceId;
	}

	public function setIvrCode($ivrCode) {
		$this->ivrCode = $ivrCode;
	}

	protected function createPreferredOrders() {
		if ($this->serviceId == 392) {
			return array(
				WapMedia::ctype_wap_dir
#				WapMedia::ctype_wap_aoc,
#				WapMedia::ctype_wap_sms,
#				WapMedia::ctype_wap_ivr
			);

		}
		else {
			return array(
				WapMedia::ctype_wap_aoc,
				WapMedia::ctype_wap_dir,
				WapMedia::ctype_wap_sms,
				WapMedia::ctype_wap_ivr
			);
		}
	}

	public function execute() {
		$query = $this->connection->createQuery('set @ctype_wap_dir = ?');
		$query->setInt(1, WapMedia::ctype_wap_dir);
		$query->open(false);

		$query = $this->connection->createQuery('set @ctype_wap_aoc = ?');
		$query->setInt(1, WapMedia::ctype_wap_aoc);
		$query->open(false);

		$query = $this->connection->createQuery('set @ctype_wap_sms = ?');
		$query->setInt(1, WapMedia::ctype_wap_sms);
		$query->open(false);

		$query = $this->connection->createQuery('set @ctype_wap_ivr = ?');
		$query->setInt(1, WapMedia::ctype_wap_ivr);
		$query->open(false);

		$query = $this->connection->createQuery(
<<<sql
			select
				i.interface_id,
				i.incoming_channel_id,
				i.channel_type_id
			from message_service.incoming_channel i
			where i.service_id = ?
			and i.telco_id = ?
			and i.enabled = 1
			and (
				   (i.channel_type_id = @ctype_wap_dir)
				or (i.channel_type_id = @ctype_wap_aoc)
				or (i.channel_type_id = @ctype_wap_sms)
				or (i.channel_type_id = @ctype_wap_ivr and i.pattern = ?)
			)
sql
		);

		$query->setInt(1, $this->serviceId);
		$query->setInt(2, $this->session->getTelcoId());
		$query->setString(3, $this->ivrCode);

		Logger::$logger->info("selector execute sql => $this->serviceId," .  $this->session->getTelcoId() . ", $this->ivrCode");



		$query->open(false);
		$rows = $query->getResultArray();

		$hash = array();

		for ($ind = 0; $ind < count($rows); $ind++) {
			$row = $rows[$ind];

			$channelTypeId = $row['channel_type_id'];

			$hash[$channelTypeId] = $row;
		}


		if ($this->session->getTelcoId() == WapMedia::telco_unk && !array_key_exists(WapMedia::ctype_wap_aoc, $hash)) {
			$hash[WapMedia::ctype_wap_aoc] = array(
				'interface_id' => -1,
				'incoming_channel_id' => -1,
				'channel_type_id' => WapMedia::ctype_wap_aoc
			);
		}

		if (!array_key_exists(WapMedia::ctype_wap_ivr, $hash)) {
			$hash[WapMedia::ctype_wap_ivr] = array(
				'interface_id' => -1,
				'incoming_channel_id' => -1,
				'channel_type_id' => WapMedia::ctype_wap_ivr
			);
		}

#		if (!array_key_exists(WapMedia::ctype_wap_sms, $hash)) {
#			$hash[WapMedia::ctype_wap_ivr] = array(
#				'interface_id' => -1,
#				'incoming_channel_id' => -1,
#				'channel_type_id' => WapMedia::ctype_wap_sms
#			);
#		}


		$orders = $this->createPreferredOrders();
		$found = false;
		$ind = 0;

		while (!$found && $ind < count($orders)) {
			$key = $orders[$ind];

			if (array_key_exists($key, $hash)) {
				$row = $hash[$key];

				$this->interfaceId = $row['interface_id'];
				$this->incomingChannelId = $row['incoming_channel_id'];
				$this->channelTypeId = $row['channel_type_id'];

				$found = true;
			}
			else
				$ind++;
		}


		Logger::$logger->info('selector->execute => ' . $this->channelTypeId);

//		if ($this->serviceId == 911) {
//			echo "#yes#";
//
//			print_r($orders);
//			print_r($hash);
//			echo "channeltypeid={$this->channelTypeId}";
//			exit;
//		}


#		if ($this->session->getMsisdn() == '66945285427') {
#			echo "motest - {$this->channelTypeId}";
#			exit;
#		}

#		if ($this->session->getMsisdn() == '66851990468') {
#			Logger::$logger->info("moz: $this->serviceId," .  $this->session->getTelcoId() . ", $this->ivrCode");	
#			 Logger::$logger->info('moz: selector->execute => ' . $this->channelTypeId);
#		}
	}

	public function getInterfaceId() {
		return $this->interfaceId;
	}

	public function getIncomingChannelId() {
		return $this->incomingChannelId;
	}

	public function getChannelTypeId() {
		return $this->channelTypeId;
	}
}



class Click {

	private $session;
	private $connection;
	private $hitId;
	private $pageId;
	private $clickTagId;

	private $serviceId;
	private $shortcode;
	private $keyword;
	private $ivrCode;
	private $affiliateId;
	private $affiliateCode;
	private $validateCode;
	private $landingPage;

	private $incomingChannelId;
	private $channelTypeId;
	private $interfaceId;

	private $clickId;

	public function __construct($session, $hitId, $clickTagId = -1) {
		$this->session = $session;
		$this->connection = $session->getConnection();
		$this->hitId = $hitId;
		$this->clickTagId = $clickTagId;

		$this->interfaceId = -1;
		$this->serviceId = -1;
		$this->incomingChannelId = -1;
		$this->channelTypeId = WapMedia::ctype_wap_ivr;
		$this->shortcode = '4707811';
		$this->keyword = 'c1';
		$this->ivrCode = '01';
		$this->affiliateId = 1;
		$this->validateCode = '';
		$this->landingPage = 1;
	}

	protected function createPreferredOrders() {
		return array(
			WapMedia::ctype_wap_dir,
			WapMedia::ctype_wap_aoc,
			WapMedia::ctype_wap_ivr,
			WapMedia::ctype_wap_sms
		);
	}

	public function retrieveBasicData() {
		$date = date('Y-m-d');

		$query = $this->connection->createQuery(
<<<sql
			select
				a.affiliate_id,
				a.affiliate_code,
				a.validate_code,
				c.campaign_id,
				p.page_id,
				t.text,
--				o.landing_page && a.landing_page && p.landing_page as landing_page
				o.landing_page || a.landing_page || p.landing_page as landing_page
			from wap.hit h
				join wap.page p using (page_id)
				join wap.affiliate a using (affiliate_id)
				join wap.campaign c using (campaign_id)
				left join wap.click_tag t on t.campaign_id = c.campaign_id and t.click_tag_id = ?

				join wap.session s on s.session_id = h.session_id and s.sys_timestamp between '{$date} 00:00:00' and '{$date} 23:59:59'
				left join message_service.telco o using (telco_id)
			where h.hit_id = ?
			and h.sys_timestamp between '{$date} 00:00:00' and '{$date} 23:59:59'
sql
		);

		$query->setInt(1, $this->clickTagId);
		$query->setInt(2, $this->hitId);

		$query->open(false);
		$rows = $query->getResultArray();

		if (count($rows) > 0) {
			$row = $rows[0];

			$this->affiliateId = $row['affiliate_id'];
			$this->affiliateCode = $row['affiliate_code'];
			$this->validateCode = $row['validate_code'];
#			$this->landingPage = $row['landing_page'];
#			$this->landingPage = Click::hardcode($this->hitId, $row['landing_page']);
			$this->landingPage = Click::hardcode(
				$this->session->getTelcoId(),
				$this->hitId,
				$row['landing_page']
			);


			$this->pageId = $row['page_id'];

			if (empty($row['text'])) {
				$this->affiliateId = abs($this->affiliateId) * -1;
				 Logger::$logger->info('is cross-sell');
			}
			else {
				 Logger::$logger->info('is not cross-sell');
			}
		}

		$query = $this->connection->createQuery(
<<<sql
			select
				s.service_id,
				ts.shortcode,
--				ts.keyword,
				coalesce(ts.keyword, s.keyword) as keyword,
				s.ivr_code
			from wap.click_tag t
				join wap.campaign c using (campaign_id)
				join message_service.service s on t.service_id = s.service_id
				left join message_service.telco_service ts on s.service_id = ts.service_id and telco_id = ?
			where t.click_tag_id = ?
sql
		);

		$query->setInt(1, $this->session->getTelcoId());
		$query->setInt(2, $this->clickTagId);
		$query->open();

		$rows = $query->getResultArray();

		if (count($rows) > 0) {
			$row = $rows[0];

			$this->serviceId = $row['service_id'];
			$this->shortcode = $row['shortcode'];
			$this->keyword = $row['keyword'];
			$this->ivrCode = $row['ivr_code'];
		}
	}

	public static function hardcode($telcoId, $hitId, $landingPage) {
		$hour = date('H');
		$ahours = array(
#			0,
#			1,
#			2,
#			3,
#			4,
#			5,
#			6,
#			7,
#			8,
#			9,
#			10,
#			11,
#			12,
#			13,
#			14,
#			15,
#			16,
#			17,
#			18,
#			19,
#			20,
#			21,
#			22,
#			23
		);

#		if ($telcoId == WapMedia::telco_ais) {
#			return 0;
#			return 1;
#		}

		if ($telcoId == WapMedia::telco_unk) {
			return 1; # always display landing page
		}
		elseif ($telcoId == WapMedia::telco_tmv || $telcoId == WapMedia::telco_rmv) {
#			return $landingPage;
			return 0;
		}
		elseif ($landingPage == 1 && array_search($hour, $ahours) !== false) {
			switch ($telcoId) {
				case WapMedia::telco_ais :
#					return (($hitId % 5) == 0)? 0: 1;
					return 0;

				case WapMedia::telco_dtc :
#					return (($hitId % 3) == 0)? 0: 1;
					return 1;

				default :
					return (($hitId % 3) == 0)? 0: 1;

			}
#			return (($hitId % 5) == 0)? 0: 1; // 20%
#			return (($hitId % 3) == 0)? 0: 1; // 33.33%
#			return (($hitId % 2) == 0)? 0: 1; // 50.00%
			
		}
		else
			return $landingPage;
	}


	public function compute() {

		$selector = new ChannelSelector($this->session);

		$selector->setServiceId($this->serviceId);
		$selector->setIvrCode($this->ivrCode);

		$selector->execute();

		$this->interfaceId = $selector->getInterfaceId();
		$this->incomingChannelId = $selector->getIncomingChannelId();
		$this->channelTypeId = $selector->getChannelTypeId();

/*
		$query = $this->connection->createQuery('set @ctype_wap_dir = ?');
		$query->setInt(1, WapMedia::ctype_wap_dir);
		$query->open();

		$query = $this->connection->createQuery('set @ctype_wap_aoc = ?');
		$query->setInt(1, WapMedia::ctype_wap_aoc);
		$query->open();

		$query = $this->connection->createQuery('set @ctype_wap_sms = ?');
		$query->setInt(1, WapMedia::ctype_wap_sms);
		$query->open();

		$query = $this->connection->createQuery('set @ctype_wap_ivr = ?');
		$query->setInt(1, WapMedia::ctype_wap_ivr);
		$query->open();

		$query = $this->connection->createQuery('
			select
				i.interface_id,
				i.incoming_channel_id,
				i.channel_type_id
			from message_service.incoming_channel i
			where i.service_id = ?
			and i.telco_id = ?
			and i.enabled = 1
			and (
				   (i.channel_type_id = @ctype_wap_dir)
				or (i.channel_type_id = @ctype_wap_aoc)
				or (i.channel_type_id = @ctype_wap_sms)
				or (i.channel_type_id = @ctype_wap_ivr and i.pattern = ?)
			)
		');

		$query->setInt(1, $this->serviceId);
		$query->setInt(2, $this->session->getTelcoId());
#		$query->setInt(2, 1); # hard coding
		$query->setString(3, $this->ivrCode);

		$query->open();
		$rows = $query->getResultArray();
		$hash = array();

		for ($ind = 0; $ind < count($rows); $ind++) {
			$row = $rows[$ind];

			$channelTypeId = $row['channel_type_id'];

			$hash[$channelTypeId] = $row;
		}

		$orders = $this->createPreferredOrders();
		$found = false;
		$ind = 0;

		while (!$found && $ind < count($orders)) {
			$key = $orders[$ind];

			if (array_key_exists($key, $hash)) {
				$row = $hash[$key];

				$this->interfaceId = $row['interface_id'];
				$this->incomingChannelId = $row['incoming_channel_id'];
				$this->channelTypeId = $row['channel_type_id'];

				$found = true;
			}
			else
				$ind++;
		}
*/
	}


	protected function newClickRecord() {

		$query = $this->connection->createQuery(
<<<sql
			insert into wap.click (
				hit_id,
				channel_type_id,
				incoming_channel_id,
				click_tag_id,
				landing_page
			)
			values (
				?,
				?,
				?,
				?,
				?
			)
sql
		);

		$query->setInt(1, $this->hitId);
		$query->setInt(2, $this->channelTypeId);
		$query->setInt(3, $this->incomingChannelId);
		$query->setInt(4, $this->clickTagId);
		$query->setInt(5, $this->landingPage);

		Logger::$logger->info("motest: hitId=$this->hitId, channelTypeId=$this->channelTypeId, incomingChannelId=$this->incomingChannelId, clickTagId=$this->clickTagId");

		$query->open();

		$this->clickId = $this->connection->lastId();

		return $this->clickId;
	}

	protected function updateProxyMessageId($proxyMessageId) {
		$date = date('Y-m-d');

		$query = $this->connection->createQuery('
			update wap.click
				set proxy_message_id = ? 
			where click_id = ?
			and sys_timestamp between ? and ?
		');

		$query->setString(1, $proxyMessageId);
		$query->setInt(2, $this->clickId);
		$query->setString(3, "$date 00:00:00");
		$query->setString(4, "$date 23:59:59");

		$query->open();
	}

	public function save() {
		$clickId = $this->newClickRecord();

#		$this->updateProxyMessageId($clickId);

		return $clickId;
	}


	protected function blockingProcess($ignoreBlock) {
		if (!$ignoreBlock) {

#			$blocker = new WapBlocker($this->session);
			$blocker = new WapClickBlocker($this->session);

			$blocker->setTelcoId($this->session->getTelcoId());
			$blocker->setMsisdn($this->session->getMsisdn());
			$blocker->setClickId($this->clickId);
			$blocker->setServiceId($this->serviceId);
//			$blocker->setAffiliateCode($this->affiliateCode);
			$blocker->setAffiliateId($this->affiliateId);

			if (($code = $blocker->execute()) != 0) {
				Logger::$logger->info("msisdn=".$this->session->getMsisdn()." => BLOCKED");

//mo				$pixee = new Pixee($this->session);
//mo				$pixee->setAffiliateId($this->affiliateId);
//mo				list($pixeeId, $pixeeFiringId, $url) = $pixee->execute(true);

#				$url->redirect();

				exit;
			}

		}
	}


	protected function execWapDir($ignoreBlock = false) {
		$code = 0;
		$moSender = null;

		Logger::$logger->info("wap-direct - msisdn=".$this->session->getMsisdn().", interfaceId=".$this->interfaceId);

		$this->blockingProcess($ignoreBlock);
#		if (!$ignoreBlock) {
#			$blocker = new WapBlocker($this->session);
#
#			$blocker->setTelcoId($this->session->getTelcoId());
#			$blocker->setMsisdn($this->session->getMsisdn());
#
#			if (($code = $blocker->execute()) != 0) {
#				Logger::$logger->info("wap-direct - msisdn=".$this->session->getMsisdn()." => BLOCKED");
#
#				return 200;
#			}
#		}

		
###		if ($this->serviceId == 392) {
###			echo 'interface id => ' . $this->interfaceId;
###			exit;
###		}


		switch ($this->interfaceId) {
			case WapMedia::inf_ais_lgc: $moSender = new LegacyMoSender(); break;
			case WapMedia::inf_dtc_cpa:
			case WapMedia::inf_dtc_sdp:
#### im curious			$moSender = new SdpMoSender();
				$moSender = new SdpConsentAoc();
			break;
			case WapMedia::inf_tmv_css: $moSender = new TmvMoSender(); break;
			case WapMedia::inf_tmh_css: $moSender = new TmhMoSender(); break;

#			case WapMedia::inf_ais_cdg: $moSender = new CdgWapAoc(); break;
			case WapMedia::inf_ais_cdg: $moSender = new CdgWapAocSecure(); break;
#			case WapMedia::inf_ais_cdg: $moSender = new CdgWapAocToken(); break;

			case WapMedia::inf_tmh_mpt: $moSender = new TmhMptAoc(); break;

		}

		if ($moSender != null) {
			$moSender->setSession($this->session);
			$moSender->setShortcode($this->shortcode);
			$moSender->setIvrCode($this->ivrCode);
			$moSender->setValidateCode($this->validateCode);
			$moSender->setText($this->keyword);
			$moSender->setAffiliateId($this->affiliateId);
			$moSender->setClickId($this->clickId);
			$moSender->setPageId($this->pageId);
			$moSender->setServiceId($this->serviceId);
			$moSender->setChannelTypeId(WapMedia::ctype_wap_dir);

##			if ($this->serviceId == 392) { echo "before"; exit; }

			$code = $moSender->execute();

##			if ($this->serviceId == 392) { echo "after"; exit; }
		}


		return $code;
	}

	protected function clickLog($type, $host = '', $path = '') {
		$session = $this->session;
		$telco = WapMedia::$telcos[$session->getTelcoId()];
		$affiliateCode = $this->affiliateCode;
		$msisdn = $session->getMsisdn();
		$pageId = $this->pageId;

		$line = sprintf(
			'CLICK %s T(%s), A(%s), M(%s), P(%05d) - %s%s',
				$type,
				$telco,
				$affiliateCode,
				substr($msisdn, 0, 4).'-'.substr($msisdn, 4, 3).'-'.substr($msisdn, 7),
				$pageId,
				$host,
				$path
		);

		$this->session->takeLog($line);
	}



	protected function execWapSms($ignoreBlock = false) {
		$this->blockingProcess($ignoreBlock);

		$command = "sms:{$this->shortcode}?body={$this->keyword}"; # android

		if ($this->session->isIPhone()) {
#			$command = "sms:{$this->shortcode};body={$this->keyword}"; # ios < 8
			$command = "sms:{$this->shortcode}&body={$this->keyword}"; # ios 8 later
		}

		$this->clickLog('WAP-SMS', $command);

		header("Connection: keep-alive");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");
		header("Cache-Control: no-cache");
		header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

		header("Location: {$command}", true, 303);


#		Logger::$logger->info("redirect - location: {$command}");

		exit;
	}

	protected function execWapAoc($ignoreBlock = false) {
#		if ($this->session->getMsisdn() == '66851990468') {
#			Logger::$logger->info("moz: yes");	
#		}

		$this->blockingProcess($ignoreBlock);


		$url = new Url();
		$url->setPath('/aoc-red.exe.php');
		$url->setParams(
			array(
				'_'	=> 1,
				'_c'	=> $this->clickId
			)
		);


		$this->clickLog('WAP-AOC', $url->getHost(), $url->getPath());

		$url->redirect();
/*
		$aoc = null;

		switch ($this->interfaceId) {
#			case WapMedia::inf_ais_cdg: $aoc = new CdgWapAoc(); break;
			case WapMedia::inf_ais_cdg: $aoc = new CdgWapAocSecure(); break;
		}

		if ($aoc != null) {
			$aoc->setSession($this->session);
			$aoc->setShortcode($this->shortcode);
			$aoc->setIvrCode($this->ivrCode);
			$aoc->setValidateCode($this->validateCode);
			$aoc->setText($this->keyword);
			$aoc->setAffiliateId($this->affiliateId);
			$aoc->setClickId($this->clickId);

			$aoc->execute();
		}
*/
	}

	public function execute() {
		global $_SERVER;

		$this->retrieveBasicData();
		$this->compute();
		$this->save();

		$shortcode = $this->shortcode;
		$keyword = $this->keyword;
		$ivrCode = $this->ivrCode;
		$validateCode = $this->validateCode;


#		$requester = new HttpRequester();
#		$requester->setUrl('http://www.google.co.th');
#		$requester->setMethod(HttpRequester::MTHD_GET);
#		$requester->execute();
#		echo $requester->getResponse()->getContent();
#		echo $requester->getResponse()->getVersion();
#		exit;

#		if ($this->session->getMsisdn() == '66945285427') {
#			echo "{$this->channelTypeId}";
#			exit;
#		}



#		if ($this->serviceId == 392) {
#			echo 'selector->execute => ' . $this->channelTypeId . ' ' . WapMedia::ctype_wap_dir;
#			exit;
#		}

//		if ($this->serviceId == 909) {
//			echo "ok={$this->channelTypeId}";
//
//			exit;
//		}


		switch ($this->channelTypeId) {
			case WapMedia::ctype_wap_dir:
				if ($this->execWapDir() == 200) {

					$path = dirname($_SERVER['REQUEST_URI']);
					$url = Web::curPageUrl(false) . $path . '/thanks.php';

#					header("location: http://m.sabver.com/sub/thanks.php");
					header("location: $url");

#					Logger::$logger->info("redirect - location: http://m.sabver.com/sub/thanks.php");
					Logger::$logger->info("redirect - location: $url");
				}

				exit;
			break;

			case WapMedia::ctype_wap_aoc:
				$this->execWapAoc();

			break;

			case WapMedia::ctype_wap_sms:

				$this->execWapSms();
			break;

			case WapMedia::ctype_wap_ivr:
				header("location: tel:*${shortcode}${ivrCode}${validateCode}");

				Logger::$logger->info("redirect - location: tel:*${shortcode}${ivrCode}${validateCode}");

				exit;
			break;
		}
	}
}

class Closer {
	
	private $hitId;
	private $session;

	public function __construct($session) {
		$this->session = $session;
		
	}

	public function setHitId($hitId) {
		$this->hitId = $hitId;

	}

	protected function save() {
		$query = $this->session->getConnection()->createQuery(
<<<sql
			insert into wap.close (
				hit_id
			)
			values (
				?
			)
sql
		);

		$query->setInt(1, $this->hitId);
		$query->open();

	}

	public function execute() {
		$this->save();

		Logger::$logger->info("kup getTelcoId = ".$this->session->getTelcoId());

		header('content-type: text/html');
#		echo '<script>window.close();</script>';
		
		header('refresh: 1; url=http://www.google.co.th');

		echo "Window is closing....";

#		header("Location: http://www.google.co.th");

#		$url = new Url();
#		$url->setPath('/');
#		$url->setParams(array());
#		$url->redirect();

#		if ($this->session->getTelcoId() == WapMedia::telco_rmv) {
#
#
#		} else {
#
#
#		}


	}
}

