<?php

require_once('com/moserv/log/logger.php');
require_once('com/moserv/net/http.php');
require_once('com/moserv/net/session.php');
require_once('com/moserv/net/redirector.php');
require_once('com/moserv/net/url.php');
require_once('com/moserv/security/rsa.php');
require_once('com/moserv/sql/connection.php');
require_once('com/moserv/util/web.php');
require_once('com/moserv/wap/wap-landing.php');
require_once('com/moserv/wap/wap-media.php');

class MoSender {

	const stat_ini = 0;
	const stat_snt = 1;
	const stat_err = 2;

	protected $session;
	protected $client;

	protected $shortcode;
	protected $ivrCode;
	protected $validateCode;
	protected $text;
	protected $affiliateId;
	protected $clickId;
	protected $pageId;
	protected $serviceId;
	protected $channelTypeId;
	protected $bindAddress;

	public function __construct() {
		$this->session =
		$this->client = null;

		$this->shortcode =
		$this->ivrCode =
		$this->validateCode =
		$this->text = '';
		$this->affiliateId = 1;
		$this->channelTypeId = WapMedia::ctype_wap_dir;
		$this->bindAddress = null;
	}

	public function setSession($session) {
		$this->session = $session;
	}

	public function setShortcode($shortcode) {
		$this->shortcode = $shortcode;
	}

	public function setIvrCode($ivrCode) {
		$this->ivrCode = $ivrCode;
	}

	public function setValidateCode($validateCode) {
		$this->validateCode = $validateCode;
	}

	public function setText($text) {
		$this->text = $text;
	}

	public function setAffiliateId($affiliateId) {
		$this->affiliateId = $affiliateId;
	}

	public function setClickId($clickId) {
		$this->clickId = $clickId;
	}

	public function setPageId($pageId) {
		$this->pageId = $pageId;
	}

	public function setChannelTypeId($channelTypeId) {
		$this->channelTypeId = $channelTypeId;
	}	

	public function setBindAddress($bindAddress) {
		$this->bindAddress = $bindAddress;
	}

	public function getContent() {
		return null;
	}

	public function getUrl() {
		return '';
	}

	public function getClient() {
		return $this->client;
	}

	public function execute() {
		$url = $this->getUrl();
		$method = $this->getMethod();
		$content = $this->getContent();
		$contentType = $this->getContentType();

		Logger::$logger->info("api request: [ url=$url; method=$method; content=$content ]");

		$this->client = $client = new HttpClient();
		$headers = $client->getHeaders();

		$client->setUrl($url);
		$client->setMethod($method);

		if ($content != null)
			$client->setContentData($content);

		if ($contentType != null)
			$headers->add('content-type', $contentType);

		$client->execute();

		$response = $client->getResponse();

		$code = $response->getCode();
		$content = $response->getContent();


		Logger::$logger->info("api response: [ code=$code; content=$content ]");

		return $code;
	}

	public function getMethod() {
#		return HttpRequester::MTHD_GET;
		return HttpClient::MTHD_GET;
	}

	public function getContentType() {
		return null;
	}

	public function setServiceId($serviceId) {
		$this->serviceId = $serviceId;
	}

	public function getBindAddress() {
		return $this->bindAddress;
	}
}

abstract class RedirectOperatorAoc extends MoSender {

	abstract protected function getParamArray();
	abstract protected function getAocUrl();

	public function execute() {
		$params = $this->getParamArray();
		$querystring = http_build_query($params);

		$url = $this->getAocUrl();

		Logger::$logger->info("wap: querystring: [{$querystring}]");
		Logger::$logger->info("wap: url: [{$url}]");
		Logger::$logger->info("wap: link=[{$url}?{$querystring}]");

#		header("location: {$url}?{$querystring}");
#		exit;


		$aocUrl = new Url($this->getAocUrl());
		$aocUrl->setParams($this->getParamArray(), true);

#		if ($this->session->getMsisdn() == "66868164860") {
#		if ($this->session->getMsisdn() == "66945285427") {
#			echo $aocUrl->toString();
#
#			exit;
#		}

#		if (preg_match('/^45981(55|44|33)/', $this->shortcode)) {

##		if ($this->session->getMsisdn() == "66989234406") {
##		if ($this->session->getMsisdn() == "66622944908") {
##			echo $aocUrl->toString();
##			echo "<br>{$this->shortcode}";
##			exit;
##		}

		$safeUrl = new Url();
#		$safeUrl->setPath('/cute/index.php');
		$safeUrl->setPath('/ais-wap/index.php');
		$safeUrl->setParams(null);
		$safeUrl->setParam('p', $this->pageId);
		$safeUrl->setParam('_', 1);
		$safeUrl->setParam('_f', 1);

		$landing = new WapLanding($this->session);
		$landing->register($this->clickId);

		$landing->addConfig($safeUrl->toString(), Redirector::rd_chin);
		$landing->addConfig($aocUrl->toString(), Redirector::rd_href);

		$landing->gotoLanding();

	}
}


class CdgWapAoc extends RedirectOperatorAoc {

	protected function getParamArray() {
		$array = array(
			'cmd' => 'exp',
			'ch' => 'WAP',
			'SN' => "{$this->shortcode}{$this->ivrCode}",
			'spsID' => $this->clickId,
#			'spName' => 707, # cp-id
#			'spName' => $this->session->getCfgVar('cpid'), # cp-id
			'spName' => $this->session->getCpId(), # cp-id
			'cct' => '10',
			'cURL' => Web::curPageUrl(false) . '/cdg-stat.exe.php'
		);

		return $array;
	}

	protected function getAocUrl() {
#		return 'http://ss1.mobileLIFE.co.th/wis/wap';
		return 'http://ss1.mobileLIFE.co.th/wis/wap/';
	}

	public static function updateStatus($msisdn, $tid, $spsid, $status, $reason) {
		$session = WapSession::create();

		$proxyMessageId = $tid;
		$clickId = $spsid;
		$date = date('Y-m-d');

		$query = $session->getConnection()->createQuery('
			update wap.click
				set proxy_message_id = ?
			where click_id = ?
			and sys_timestamp between ? and ?
		');

		$query->setString(1, $proxyMessageId);
		$query->setInt(2, $clickId);
		$query->setString(3, "$date 00:00:00");
		$query->setString(4, "$date 23:59:59");

		$query->open();

		Logger::$logger->info("cdg wap aoc: [ msisdn=$msisdn, spsid=$spsid, tid=$tid, status=$status, reason=$reason ]");
	}
}

class CdgWapAocSecure extends CdgWapAoc {
	protected function getParamArray() {
		global $_SERVER;
		$home		= dirname(dirname($_SERVER['DOCUMENT_ROOT']));
		$publicKey	= "file://{$home}/certs/wap_id_rsa_public.pem";
		$landingUrl	= Web::curPageUrl(false) . '/cdg-stat.exe.php?spsID=' . $this->clickId;

		Logger::$logger->info("publicKey => $publicKey");

		$channel	= 'WAP';
		$command	= 's_exp';
		$serviceNo	= "{$this->shortcode}{$this->ivrCode}";
		$sessionId	= $this->clickId; # transaction id

		$contentUrl	= base64_encode(Rsa::encryptByPublicKey($landingUrl, $publicKey));
#		$cpId		= 707; # content provider id => t-stone
#		$cpId		= $this->session->getCfgVar('cpid'); # content provider id
		$cpId		= $this->session->getCpId(); # content provider id
		$cct		= '10'; # may be '09'

##		if (preg_match('/^45981(55|44|33)/', $this->shortcode)) {
####		if (preg_match('/^[34]5982(33|44|55)/', $this->shortcode)) {
####			$serviceNo{0} = '3';
####			$cct = '09';
####		}


		$array = array(
			'ch'		=> $channel,
			'cmd'		=> $command,
			'SN'		=> $serviceNo,
			'spsID'		=> $sessionId,
			'cURL'		=> $contentUrl,
			'spName'	=> $cpId,
			'cct'		=> $cct
		);


		Logger::$logger->info("array => " . print_r($array, true));
		Logger::$logger->info("url => $landingUrl");

#		if ($_REQUEST['x'] == 1)
#			Logger::$logger->info("xxx => " . print_r($array, true));

		return $array;
	}

	protected function getAocUrl() {
#		return 'http://ss1.mobilelife.co.th/wis/wap';

###		if (preg_match('/^[34]641(133|144|244|155|255)/', $this->shortcode)) {
####		if (preg_match('/^[34]5982(33|44|55)/', $this->shortcode)) {
####			return 'http://ss2.mobilelife.co.th/wis/wap/';
####		}
####		else {
			return 'http://ss1.mobilelife.co.th/wis/wap/';
####		}
	}
}

class SdpConsentAoc extends RedirectOperatorAoc {

	private function getProductId() {
		$connection = $this->session->getConnection();
		$query = $connection->createQuery("
			select
				n.telco_service_id
			from wap.click c
				join wap.click_tag t using (click_tag_id)
				join message_service.outgoing_channel n using (service_id)
			where c.click_id = ?
			and n.telco_id = ?
			limit 1
		");

		$query->setInt(1, $this->clickId);
		$query->setInt(2, WapMedia::telco_dtc);

		$query->open();

		$rows = $query->getResultArray();

		if (count($rows) > 0) {
			return $rows[0]['telco_service_id'];
		}
		else {
			$url = new Url();

			return ($url->getHost() == 'm.kangped.com' || $url->getHost() == 'm.kodzed.com')? '42590020001': '47070202001'; # hardcoded
		}
	}

	protected function getParamArray() {
		$url = new Url();

		$array = array(
#			'cpid'		=> $this->session->getCfgVar('cpid'),
			'cpid'		=> $this->session->getCpId(),
			'pid'		=> $this->getProductId(),
			'lc'		=> 'th',
			'cancelurl'	=> Web::curPageUrl(false),
			'backurl'	=> Web::curPageUrl(false),
#			'cc'		=> ($url->getHost() == 'm.kangped.com')? '027147664': '027147596',
			'cc'		=> $this->session->getCfgVar('phone'),
			'ch'		=> 'wap',
			'referral'	=> $this->clickId
		);

		return  $array;
	}

	protected function getAocUrl() {
		return 'https://consentprt.dtac.co.th/webaoc/aocservice';
	}
}

class SdpConsentAocToken extends SdpConsentAoc {

	private function getToken($cpid, $password, $productid) {
##		$tokenUrl = new Url('http://saraburi.moserv.mobi/tokenforaoc/gettoken');
		$tokenUrl = new Url('http://saraburi.moserv.mobi:44380/tokenforaoc/gettoken');

		$http = new HttpClient();
		$http->getHeaders()->add('Content-Type', 'application/json; charset=utf-8');
		$http->setMethod(HttpClient::MTHD_POST);
		$http->setUrl($tokenUrl->toString());
		$http->setContentData(
			json_encode(
				array(
					'cpid'=> $cpid,
					'password'=> $password,
					'productid'=> $productid
				)
			)
		);

		$http->execute();
		$response = $http->getResponse();

		$content = $response->getContent();
		$record = json_decode($content, true);
		$token = $record['token'];

#		echo "get token => {$response->getCode()} {$response->getContent()}";
#		exit;

		Logger::$logger->info("get token => {$response->getCode()} {$response->getContent()}");

		return $token;
	}

	protected function getParamArray() {

		$url = new Url();
		$params = SdpConsentAoc::getParamArray();
		$token = $this->getToken(
			$this->session->getCpId(),
			$this->session->getCfgVar('dtac_token_password'),
			$params['pid']
		);

		$params = $params + array(
			'backtohomemsg'		=> 2,

			'clientreference'	=> $params['referral'],
			'notifylandingurl'	=> "{$url->getHost()}/dtac-token/notify.exe.php",
			'token'			=> $token
		);

		unset($params["ch"]);

		return $params;
	}
}


/*
class SdpWapAoc extends MoSender {
	protected function getParamArray() {
		$array = array(
			'c' => $this->clickId,
			'_' => '1'
		);

		return $array;
	}

	protected function getAocUrl() {
		return 'http://m.sabver.com/aoc.php';
	}

	public function execute() {
		$params = $this->getParamArray();
		$querystring = http_build_query($params);

		$url = $this->getAocUrl();

		header("location: {$url}?{$querystring}");
		exit;
	}
}
*/


class LegacyMoSender extends MoSender {

	public function getUrl() {
		$url = 'http://103.246.17.89:2500/legacy-backend/sms'.
			'?CTYPE=TEXT&'.
			'&CMD=DLVRMSG'.
	#		'&TRANSID=00000020029577202'.
			'&CONTENT=%s'.	# text
			'&NTYPE=GSM'.	# juz always do this like gsm :P
			'&TO=%s'.	# shortcode
			'&FROM=%s'.	# msisdn
			'&FET=SMS'.
			'&CODE=REQUEST'.
			'&PROXY_ID=%s'.
			'&PROXY_MESSAGE_ID=%s'.
			'&CHANNEL_TYPE_ID=%d';

		return sprintf(
			$url,

			urlencode($this->text),
			urlencode($this->shortcode),
			urlencode($this->session->getMsisdn()),
			urlencode($this->affiliateId),
			urlencode($this->clickId),
			$this->channelTypeId
		);
	}
}

class CpaMoSender extends MoSender {

	public function getContent() {

		$xml =
			'<?xml version="1.0" encoding="UTF-8" ?>'.
			'<cpa-mobile-request>'.
	#			'<txid>48364647441</txid>'.
				'<authentication>'.
					'<user>dtac</user>'.
					'<password>T_sTonE</password>'.
				'</authentication>'.
				'<destination>'.
					'<msisdn>%s</msisdn>'.			# shortcode
					'<serviceid>%s</serviceid>'.		# shortcode
				'</destination>'.
				'<originator>'.
					'<msisdn>%s</msisdn>'.			# msisdn
				'</originator>'.
				'<wap>'.
					'<proxy-id>%d</proxy-id>'.			# affiliateId
					'<proxy-message-id>%s</proxy-message-id>'.		# clickId
					'<channel-type-id>%d</channel-type-id>'.	# channel type id
					'<from>%s</from>'.			# shortcode
					'<content-id>%s%s</content-id>'.	# shortcode ivrCode
				'</wap>'.
				'<message>'.
					'<header>'.
						'<timestamp>%s</timestamp>'.	# timestamp yyyymmddhhmiss
					'</header>'.
					'<sms>'.
						'<msg>%s</msg>'.		# text
						'<msgtype>E</msgtype>'.
						'<encoding>0</encoding>'.
					'</sms>'.
				'</message>'.
				'<startCallDateTime>%s</startCallDateTime>'.	# timestamp yyyymmddhhmiss
			'</cpa-mobile-request>';

		$timestamp = date('YmdHis');

		return sprintf(
			$xml,

			$this->shortcode,
			$this->shortcode,
			$this->session->getMsisdn(),
			$this->affiliateId,
			$this->clickId,
			$this->channelTypeId,
			$this->shortcode,
			$this->shortcode,
			$this->ivrCode,
			$timestamp,
			$this->text,
			$timestamp
		);
	}

	public function getUrl() {
		return 'http://103.246.17.89:3000/dtac-backend/sms';
	}

	public function getMethod() {
#		return HttpRequester::MTHD_POST;
		return HttpClient::MTHD_POST;
	}

	public function getContentType() {
		return 'text/xml';
	}
}


class SdpMoSender extends CpaMoSender {
/*
	private function getTranId($n) {
		srand((double) microtime() * 1000000);

		$nums = array();

		for ($i = 0; $i < $n; $i++) {
			$nums[] = rand(0, 9);
		}

		$tranId = implode('', $nums);

		return $tranId;
	}
*/
	private function getTelcoServiceId() {
		$connection = $this->session->getConnection();
		$query = $connection->createQuery('
			select
				telco_service_id
			from message_service.outgoing_channel
			where service_id = ?
			and telco_id = ?
			and incoming_channel_id = ?
			and sms_cat_id = ?
			and sms_action_id = ?
		');

		$query->setInt(1, $this->serviceId);
		$query->setInt(2, 3); # dtac
		$query->setInt(3, -1);
		$query->setInt(4, 2); # first content
		$query->setInt(5, 4);

		$query->open();

		$rows = $query->getResultArray();


		return (count($rows) > 0)? $rows[0]['telco_service_id']: '';
	}

	private function getTranId() {
		## Format txid = [flag,0][1]+[cpid][3]+[ddhh24mmss][8]+[seq][3]
		## Moserv txid = 0 607 01 185000 001
		## 060701185000001
		## Example:
		## 0 450 28 133030 001 (CP->SDP)
		## 1 032 28 133030 002 (SDP->CP)
		
		srand((double) microtime() * 1000000);

		$flag = 0;
		$cpId = 607;
		$date = date('dHis');
//		$seq = 1;
		$seq = rand(1, 999);

		$tranId = sprintf('%01d%03d%s%03d', $flag, $cpId, $date, $seq);

		return $tranId;
	}


	public function getContent() {

		$xml =
			'<?xml version="1.0" encoding="UTF-8" ?>'.
			'<cpa-mobile-request>'.
				'<txid>%s</txid>'.
				'<authentication>'.
					'<user>dtac</user>'.
					'<password>T_sTonE</password>'.
				'</authentication>'.
				'<destination>'.
					'<msisdn>%s</msisdn>'.			# shortcode
					'<serviceid>%s</serviceid>'.		# shortcode
				'</destination>'.
				'<originator>'.
					'<msisdn>%s</msisdn>'.			# msisdn
				'</originator>'.
				'<wap>'.
					'<proxy-id>%d</proxy-id>'.			# affiliateId
					'<proxy-message-id>%s</proxy-message-id>'.		# clickId
					'<channel-type-id>%d</channel-type-id>'.	# channel type id
					'<from>%s</from>'.			# shortcode
					'<content-id>%s%s</content-id>'.	# shortcode ivrCode
				'</wap>'.
				'<message>'.
					'<header>'.
						'<timestamp>%s</timestamp>'.	# timestamp yyyymmddhhmiss
					'</header>'.
					'<sms>'.
						'<msg>%s</msg>'.		# text
						'<msgtype>E</msgtype>'.
						'<encoding>0</encoding>'.
					'</sms>'.
				'</message>'.
				'<startCallDateTime>%s</startCallDateTime>'.	# timestamp yyyymmddhhmiss
			'</cpa-mobile-request>';

//		$tranId = $this->getTelcoServiceId();
		$tranId = $this->getTranId();
		$timestamp = date('YmdHis');

		return sprintf(
			$xml,

			$tranId,
			$this->shortcode,
			$this->shortcode,
			$this->session->getMsisdn(),
			$this->affiliateId,
			$this->clickId,
			$this->channelTypeId,
			$this->shortcode,
			$this->shortcode,
			$this->ivrCode,
			$timestamp,
			$this->text,
			$timestamp
		);
	}

	public function  getUrl() {
#		$host = $this->session->getCfgVar('dtac_mo_host');
#		$port = $this->session->getCfgVar('dtac_mo_port');
#		$url = new Url("http:{$host}:{$port}/dtac-backend/sms");
#
#		return $url->toString();

#		return 'http://103.246.17.89:3500/dtac-backend/sms';
		return 'http://103.7.56.235:3500/dtac-backend/sms';
	}
}


class ThirdPartySender extends MoSender {

	protected $serviceId;

	public function __construct() {
		parent::__construct();

		$this->serviceId = null;
	}

	public function setServiceId($serviceId) {
		$this->serviceId = $serviceId;
	}
	
	public function getContent() {
		$xml =
			'<?xml version="1.0" encoding="UTF-8"?>'.
			'<request type="mo" id="%s">'.			# uuid
				'<body>'.
					'<number>%s</number>'.		# msisdn
					'<service-id>%s</service-id>'.	# service id
					'<ud>%s</ud>'.			# text (keyword)
					'<authorization>aLpEI46oLuwakq/G7PGGIfSWSdpy4rdm</authorization>'.
				'</body>'.
			'</request>';

		return sprintf(
			$xml,

			$this->clickId,
			$this->session->getMsisdn(),
			$this->serviceId,
			$this->text
		);
	}

	public function execute() {
		$code = parent::execute();
	
		$content = $this->client->getResponse()->getContent();
		Logger::$logger->info("ThirdPartySender getReturn: [ code=$code; content=$content ]");

		if ($code == 200) {

			$xml = new SimpleXMLElement($content);

			$xstatus = $xml->xpath("/response/body/status");
			$xdesc   = $xml->xpath("/response/body/description");

			$status = $xstatus[0][0];
			$desc   = $xdesc[0][0];
	
			Logger::$logger->info("ThirdPartySender response: [ code=$code; status=$status; description=$desc ]");
			return ($status == 0 && strtolower($desc) == "success")? 200: 0;
		}

		return $code;
	}

	public function getUrl() {
		return 'http://203.151.233.215/partner/true3api.svc/';
	}

	public function getMethod() {
#		return HttpRequester::MTHD_POST;
		return HttpClient::MTHD_POST;
	}

	public function getContentType() {
		return 'text/xml';
	}
}

class CssMoSender extends MoSender {

	protected $serviceId;

	public function __construct() {
		parent::__construct();

		$this->serviceId = null;
	}

	public function getServiceId() {
		if ($this->serviceId != null)
			return $this->serviceId;

		$connection = $this->session->getConnection();

		$query = $connection->createQuery("
			select
				o.telco_service_id
			from message_service.incoming_channel i
				join message_service.outgoing_channel o using (service_id, telco_id)
			where i.telco_id = ?
			and i.shortcode = ?
			and ? rlike i.pattern
			and i.channel_type_id = ?
			and o.sms_cat_id = 2
			and o.price > 0
			limit 1
		");

		$query->setInt(1, $this->session->getTelcoId());
		$query->setString(2, $this->shortcode);
		$query->setString(3, $this->text);
		$query->setInt(4, $this->channelTypeId); # always wap direct sub

		$query->open();

		$rows = $query->getResultArray();

		$this->serviceId = (count($rows) > 0)? $rows[0]['telco_service_id']: "0";

		return $this->serviceId;
	}


	public function getContent() {
		$xml =
			'<?xml version="1.0" encoding="ISO-8859-1"?>'.
			'<message id="%s">'.	# click id
				'<sms type="mo">'.
					'<retry count="0" max="0"/>'.
					'<destination messageid="%s">'.	# click id
						'<address>'.
							'<number type="abbreviated">%s</number>'.	# shortcode
						'</address>'.
					'</destination>'.
					'<source>'.
						'<address>'.
							'<number type="international">%s</number>'.	# msisdn
						'</address>'.
					'</source>'.
					'<ud type="text">%s</ud>'.	# text
					'<scts>%s</scts>'. # timestamp yyyy-mm-ddThh:mi:ssZ
					'<service-id>%s</service-id>'.	# service id
				'</sms>'.
				'<from>wap</from>'.
				'<to>WapAdapter::%s</to>'.	# service id
				'<proxy-id>%s</proxy-id>'.	# affiliate id
				'<proxy-message-id>%s</proxy-message-id>'.	# click id
				'<channel-type-id>%d</channel-type-id>'.	# channel type id
			'</message>';

		$timestamp = date('Y-m-d').'T'.date('H:i:s').'Z';
		$serviceId = $this->getServiceId();

		return sprintf(
			$xml,

			$this->clickId,
			$this->clickId,
			$this->shortcode,
			$this->session->getMsisdn(),
			$this->text,
			$timestamp,
			$serviceId,
			$serviceId,
			$this->affiliateId,
			$this->clickId,
			$this->channelTypeId
		);
	}

	public function execute() {

		$caller = new ThirdPartySender();

		$caller->setSession($this->session);
		$caller->setShortcode($this->shortcode);
		$caller->setIvrCode($this->ivrCode);
		$caller->setValidateCode($this->validateCode);
		$caller->setText($this->text);
		$caller->setAffiliateId($this->affiliateId);
		$caller->setClickId($this->clickId);
		$caller->setServiceId($this->getServiceId());

		if (($code = $caller->execute()) != 200 ) {
			return $code;
		}

		return parent::execute();
	}

	public function getMethod() {
#		return HttpRequester::MTHD_POST;
		return HttpClient::MTHD_POST;
	}

	public function getContentType() {
		return 'text/xml';
	}
}


class TmvMoSender extends CssMoSender {

	public function getUrl() {
		return 'http://103.246.17.89:4000/truemove-backend/sms';
	}

}

class TmhMoSender extends CssMoSender {

	public function getUrl() {
		return 'http://103.246.17.89:5000/truemoveh-backend/sms';
	}

}

class VietnamMoSender extends MoSender {

	public function __construct() {
		parent::__construct();
	}

	public function execute() {
		$code = parent::execute();
		return $code;
	}

	public function getUrl() {
		$url = new Url('http://mis.etracker.cc/VNWAPAOC/WAPMORequest.aspx');

		$url->setParam('telcoid', $this->session->getTelcoId());
		$url->setParam('shortcode', $this->shortcode);
		$url->setParam('keyword', $this->text);
		$url->setParam('refid', $this->clickId);
		

		return $url->toString();
	}

	public function getMethod() {
		return HttpClient::MTHD_GET;
	}

}

