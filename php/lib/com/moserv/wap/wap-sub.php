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

	protected $timeout;

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

		$this->timeout = -1;
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
		return null;
	}

	public function getClient() {
		return $this->client;
	}

	public function execute() {
		$url = $this->getUrl();
		$method = $this->getMethod();
		$content = $this->getContent();
		$contentType = $this->getContentType();

		Logger::$logger->info("api request: [ url={$url->toString()}; method=$method; content=$content ]");

		$this->client = $client = new HttpClient();
		$headers = $client->getHeaders();

		$client->setUrl($url->toString());
		$client->setMethod($method);

		if ($this->timeout != -1) {
			$client->setTimeout($this->timeout);
		}

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

	public function getShortcode() {
		return $this->shortcode;
	}

	public function getIvrCode() {
		return $this->ivrCode;
	}

	public function getValidateCode() {
		return $this->validateCode;
	}

	public function getText() {
		return $this->text;
	}

	public function getAffiliateId() {
		return $this->affiliateId;
	}

	public function getClickId() {
		return $this->clickId;
	}

	public function getPageId() {
		return $this->pageId;
	}

	public function getChannelTypeId() {
		return $this->channelTypeId;
	}	

	public function getBindAddress() {
		return $this->bindAddress;
	}

	public function getCpId() {
		return $this->session->getCpId();
	}


	public function getTelcoId() {
		return $this->session->getTelcoId();
	}

	public function setTimeout($timeout) {
		$this->timeout = $timeout;
	}
}

abstract class RedirectOperatorAoc extends MoSender {

	abstract protected function getParamArray();
	abstract protected function getAocUrl();

	public function execute() {
#		$params = $this->getParamArray();
#		$querystring = http_build_query($params);

		$url = $this->getAocUrl();

#		Logger::$logger->info("wap: querystring: [{$querystring}]");
#		Logger::$logger->info("wap: url: [{$url}]");
#		Logger::$logger->info("wap: link=[{$url}?{$querystring}]");

		$aocUrl = new Url($this->getAocUrl()->toString());
		$aocUrl->setParams($this->getParamArray(), true);

		$safeUrl = new Url();
		$safeUrl->setPath('/ais-wap/index.php');
		$safeUrl->setParams(null);
		$safeUrl->setParam('p', $this->getPageId());
		$safeUrl->setParam('_', 1);
		$safeUrl->setParam('_f', 1);

		$landing = new WapLanding($this->session);
		$landing->register($this->getClickId());

		$landing->addConfig($safeUrl->toString(), Redirector::rd_chin);
		$landing->addConfig($aocUrl->toString(), Redirector::rd_href);

		$landing->gotoLanding();
	}
}


class CdgWapAoc extends RedirectOperatorAoc {

	protected function getParamArray() {
		$url = new Url();

		$array = array(
			'cmd'	=> 'exp',
			'ch'	=> 'WAP',
			'SN'	=> "{$this->getShortcode()}{$this->getIvrCode()}",
			'spsID'	=> $this->getClickId(),
			'spName'=> $this->getCpId(), # cp-id
			'cct'	=> '10',
			'cURL'	=> "{$url->toString(Url::TOK_PORT)}/cdg-stat.exe.php"
		);

		return $array;
	}

	protected function getAocUrl() {
		return new Url('http://ss1.mobileLIFE.co.th/wis/wap/');
	}

	public static function updateStatus($msisdn, $tid, $spsid, $status, $reason) {
		$session = WapSession::create();

		$proxyMessageId = $tid;
		$clickId = $spsid;
		$date = date('Y-m-d');

		$query = $session->getConnection()->createQuery(
<<<sql
			update wap.click
				set proxy_message_id = ?
			where click_id = ?
			and sys_timestamp between ? and ?
sql
		);

		$query->setString(1, $proxyMessageId);
		$query->setInt(2, $clickId);
		$query->setString(3, "{$date} 00:00:00");
		$query->setString(4, "{$date} 23:59:59");

		$query->open();

		Logger::$logger->info("cdg wap aoc: [ msisdn=$msisdn, spsid=$spsid, tid=$tid, status=$status, reason=$reason ]");
	}
}

class CdgWapAocSecure extends CdgWapAoc {

	protected function encrypt($data) {
		global $_SERVER;

		$home		= dirname(dirname($_SERVER['DOCUMENT_ROOT']));
		$publicKey	= "file://{$home}/certs/wap_id_rsa_public.pem";

		$encrypted	= base64_encode(Rsa::encryptByPublicKey($data, $publicKey));

		return $encrypted;
	}

	protected function getLandingUrl() {
		$url = new Url();
		$url->setParams(null);
		$url->setPath('/cdg-stat.exe.php');
		$url->setParam('spsID', $this->getClickId());

		return $url;
	}

	protected function getParamArray() {
//		global $_SERVER;
//		$url = new Url();

//		$home		= dirname(dirname($_SERVER['DOCUMENT_ROOT']));
//		$publicKey	= "file://{$home}/certs/wap_id_rsa_public.pem";
//		$landingUrl	= "{$url->toString(Url::TOK_PORT)}/cdg-stat.exe.php?spsID={$this->getClickId()}";

//		Logger::$logger->info("publicKey => $publicKey");

		$channel	= 'WAP';
		$command	= 's_exp';
		$serviceNo	= "{$this->getShortcode()}{$this->getIvrCode()}";
		$sessionId	= $this->getClickId(); # transaction id

//		$contentUrl	= base64_encode(Rsa::encryptByPublicKey($landingUrl, $publicKey));
//		$contentUrl	= $this->encrypt($landingUrl);
		$contentUrl	= $this->encrypt($this->getLandingUrl()->toString());

		$cpId		= $this->getCpId(); # content provider id
		$cct		= '10'; # may be '09'

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
//		Logger::$logger->info("url => $landingUrl");

		return $array;
	}

	protected function getAocUrl() {
		return new Url('http://ss1.mobilelife.co.th/wis/wap/');
	}
}


class CdgWapAocToken extends CdgWapAocSecure {
/*
	protected function getToken() {
		$header = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9';

		$sn = '464102502';
		$spsID = rand();

		$cURL = "C792BJtfyZh9uOtQX8yFMtSptWrEC5/hXTVYdYFXcX2mc4bn/L73TZ3KfkdpC/vRP1cSpfWSMuyTtvh9zf9goWBN8YLZqEmDDbxxxoRhseh/UgckSm75c5U0yptKPa+6JgMyqkNYMG5W38q80j8/28eXebDvdjGMW/T6gNd4po3ulVJE0Jxm75KC3Z2DWsClKSp7lSlS2g6/gk8Df7U5NvrGXz/S1audDp3ZbZ1yo3ywh6KWFkMPlattB76IPze4ULBcO+PUN60TkF2Lw/odW3AijbRM1Nc9WZnP1IPGW/TLvV9WBI3/DgGIh+xNDq/au3hIrzPGZIYGpjyEK5LMFQ==";

		$cURL = str_replace("%2B","+",$cURL);
		$cURL = str_replace("%2F","/",$cURL);
		$cURL = str_replace("%3D","=",$cURL);

		$tm = round(microtime(true) * 1000);

		$arr = array('SN'=>$sn,'spsID'=>"$spsID",'cURL'=>$cURL,'timestamp'=>$tm);

		$payload = json_encode($arr);
		$payload = base64_encode($payload);
		$payload = str_replace("=","",$payload);


		$usename = "464102500000000";
		$password = "6yUeRDlzWzl4gnc";
		$secret = "$usename:$password";

		$signature = hash_hmac('sha256',$header.".".$payload, $secret);

		$signature = hex2bin("$signature");
		$signature = base64_encode($signature);
		$signature = str_replace("=","",$signature);
		$signature = str_replace("/","_",$signature);
		$signature = str_replace("+","-",$signature);

		$token = $header. "." . $payload . "." . $signature;

		return $token;
	}
*/
	protected function getToken() {
		$secret = "{$this->getShortcode()}00000000:Apz6v8Fb0eahWVu";

		if ($this->getPageId() == 1896) {

			$secret = "{$this->getShortcode()}00000000:6yUeRDlzWzl4gnc";
		}

//		$cURL = "C792BJtfyZh9uOtQX8yFMtSptWrEC5/hXTVYdYFXcX2mc4bn/L73TZ3KfkdpC/vRP1cSpfWSMuyTtvh9zf9goWBN8YLZqEmDDbxxxoRhseh/UgckSm75c5U0yptKPa+6JgMyqkNYMG5W38q80j8/28eXebDvdjGMW/T6gNd4po3ulVJE0Jxm75KC3Z2DWsClKSp7lSlS2g6/gk8Df7U5NvrGXz/S1audDp3ZbZ1yo3ywh6KWFkMPlattB76IPze4ULBcO+PUN60TkF2Lw/odW3AijbRM1Nc9WZnP1IPGW/TLvV9WBI3/DgGIh+xNDq/au3hIrzPGZIYGpjyEK5LMFQ==";


//// mo insert
//		$url = new Url();
//		$landingUrl	= "{$url->toString(Url::TOK_PORT)}/cdg-stat.exe.php?spsID={$this->getClickId()}";
//
//		if ($this->session->getMsisdn() == '66817204478') {
//			echo $cURL;
//			exit;
//		}
//// mo insert

		$header = base64_encode(json_encode(array(
			'alg' => 'HS256',
			'typ' => 'JWT'
		)));

		$payload = str_replace('=', '',
			base64_encode(json_encode(array(
				'SN'		=> "{$this->getShortcode()}{$this->getIvrCode()}",
				'spsID'		=> $this->getClickId(),
//				'cURL'		=> "{$url->toString(Url::TOK_PORT)}/cdg-stat.exe.php", // need to be encrypted
//				'cURL'		=> $cURL,
//				'cURL'		=> $this->encrypt($landingUrl),
				'cURL'		=> $this->encrypt($this->getLandingUrl()->toString()),
				'timestamp'	=> round(microtime(true) * 1000)
			)))
		);

		$signature = str_replace(array('=', '+', '/'), array('', '-', '_'),
			base64_encode(hex2bin(hash_hmac(
				'sha256',
				"{$header}.{$payload}",
				$secret
			)))
		);

		$token = "{$header}.{$payload}.{$signature}";

		return $token;
	}

	protected function getParamArray() {
		$params = parent::getParamArray();
		$params['token'] = $this->getToken();
		$params['cct'] = '09';

		unset($params['cURL']);
		unset($params['spsID']);

//		if ($this->session->getMsisdn() == '66817204478') {
//			print_r($params);
//			exit;
//		}
			
		return $params;
	}

}

class SdpConsentAoc extends RedirectOperatorAoc {

	private function getProductId() {
		$connection = $this->session->getConnection();
		$query = $connection->createQuery(
<<<sql
			select
				n.telco_service_id
			from wap.click c
				join wap.click_tag t using (click_tag_id)
				join message_service.outgoing_channel n using (service_id)
			where c.click_id = ?
			and n.telco_id = ?
			limit 1
sql
		);

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
			'cpid'		=> $this->getCpId(),
			'pid'		=> $this->getProductId(),
			'lc'		=> 'th',
			'cancelurl'	=> $url->toString(Url::TOK_PORT),
			'backurl'	=> $url->toString(Url::TOK_PORT),
#			'cc'		=> ($url->getHost() == 'm.kangped.com')? '027147664': '027147596',
			'cc'		=> $this->session->getCfgVar('phone'),
			'ch'		=> 'wap',
			'referral'	=> $this->getClickId()
		);

		return  $array;
	}

	protected function getAocUrl() {
		return new Url('https://consentprt.dtac.co.th/webaoc/aocservice');
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
					'cpid'		=> $cpid,
					'password'	=> $password,
					'productid'	=> $productid
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
			'notifylandingurl'	=> "{$url->toString(Url::TOK_PORT)}/dtac-token/notify.exe.php",
			'token'			=> $token
		);

		unset($params["ch"]);

		return $params;
	}
}


class LegacyMoSender extends MoSender {

	public function getUrl() {
		$url = new Url('http://103.246.17.89:2500/legacy-backend/sms');

		$url->setParams(array(
			'CTYPE'			=> 'TEXT',
			'CMD'			=> 'DLVRMSG',
			'CONTENT'		=> $this->getText(),
			'NTYPE'			=> 'GSM',
			'TO'			=> $this->getShortcode(),
			'FROM'			=> $this->getMsisdn(),
			'FET'			=> 'SMS',
			'CODE'			=> 'REQUEST',
			'PROXY_ID'		=> $this->getAffiliateId(),
			'PROXY_MESSAGE_ID'	=> $this->getClickId(),
			'CHANNEL_TYPE_ID'	=> $this->getChannelTypeId()
		));


		return $url;
	}
}

class CpaMoSender extends MoSender {

	public function getContent() {
		$timestamp = date('YmdHis');

		$xml =
<<<xml
<?xml version="1.0" encoding="UTF-8" ?>
<cpa-mobile-request>
	<authentication>
		<user>dtac</user>
		<password>T_sTonE</password>
	</authentication>
	<destination>
		<msisdn>{$this->getShortcode()}</msisdn>
		<serviceid>{$this->getShortcode()}</serviceid>
	</destination>
	<originator>
		<msisdn>{$this->getMsisdn()}</msisdn>
	</originator>
	<wap>
		<proxy-id>{$this->getAffiliateId()}</proxy-id>
		<proxy-message-id>{$this->getClickId()}</proxy-message-id>
		<channel-type-id>{$this->getChannelTypeId()}</channel-type-id>
		<from>{$this->getShortcode()}</from>
		<content-id>{$this->getShortcode()}{$this->getIvrCode()}</content-id>
	</wap>
	<message>
		<header>
			<timestamp>{$timestamp}</timestamp>
		</header>
		<sms>
			<msg>{$this->getText()}</msg>
			<msgtype>E</msgtype>
			<encoding>0</encoding>
		</sms>
	</message>
	<startCallDateTime>{$timestamp}</startCallDateTime>
</cpa-mobile-request>
xml
		;

		return $xml;
	}

	public function getUrl() {
		return new Url('http://103.246.17.89:3000/dtac-backend/sms');
	}

	public function getMethod() {
		return HttpClient::MTHD_POST;
	}

	public function getContentType() {
		return 'text/xml';
	}
}


class SdpMoSender extends CpaMoSender {
	private function getTelcoServiceId() {
		$connection = $this->session->getConnection();
		$query = $connection->createQuery(
<<<sql
			select
				telco_service_id
			from message_service.outgoing_channel
			where service_id = ?
			and telco_id = ?
			and incoming_channel_id = ?
			and sms_cat_id = ?
			and sms_action_id = ?
sql
		);

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
		$timestamp = date('YmdHis');

		$xml =
<<<xml
<?xml version="1.0" encoding="UTF-8" ?>
<cpa-mobile-request>
	<txid>{$this->getTranId()}</txid>
	<authentication>
		<user>dtac</user>
		<password>T_sTonE</password>
	</authentication>
	<destination>
		<msisdn>{$this->getShortcode()}</msisdn>
		<serviceid>{$this->getShortcode()}</serviceid>
	</destination>
	<originator>
		<msisdn>{$this->getMsisdn()}</msisdn>
	</originator>
	<wap>
		<proxy-id>{$this->getAffiliateId()}</proxy-id>
		<proxy-message-id>{$this->getClickId()}</proxy-message-id>
		<channel-type-id>{$this->getChannelTypeId()}</channel-type-id>
		<from>{$this->getShortcode()}</from>
		<content-id>{$this->getShortcode()}{$this->getIvrCode()}</content-id>
	</wap>
	<message>
		<header>
			<timestamp>{$timestamp}</timestamp>
		</header>
		<sms>
			<msg>{$this->getText()}</msg>
			<msgtype>E</msgtype>
			<encoding>0</encoding>
		</sms>
	</message>
	<startCallDateTime>{$timestamp}</startCallDateTime>
</cpa-mobile-request>
xml
		;

		return $xml;
	}

	public function  getUrl() {
		return new Url('http://103.7.56.235:3500/dtac-backend/sms');
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
<<<xml
<?xml version="1.0" encoding="UTF-8"?>
<request type="mo" id="{$this->clickId}">
	<body>
		<number>{$this->session->getMsisdn()}</number>
		<service-id>{$this->serviceId}</service-id>
		<ud>{$this->text}</ud>
		<authorization>aLpEI46oLuwakq/G7PGGIfSWSdpy4rdm</authorization>
	</body>
</request>
xml
		;

		return $xml;
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
		return new Url('http://203.151.233.215/partner/true3api.svc/');
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

	protected $telcoServiceId;

	public function __construct() {
		parent::__construct();

		$this->telcoServiceId = null;
	}

	public function getTelcoServiceId() {
		if ($this->telcoServiceId != null)
			return $this->telcoServiceId;

		$connection = $this->session->getConnection();

		$query = $connection->createQuery(
<<<sql
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
sql
		);

		$query->setInt(1, $this->getTelcoId());
		$query->setString(2, $this->getShortcode());
		$query->setString(3, $this->getText());
		$query->setInt(4, $this->getChannelTypeId()); # always wap direct sub

		$query->open();

		$rows = $query->getResultArray();

		$this->telcoServiceId = (count($rows) > 0)? $rows[0]['telco_service_id']: "0";

		return $this->telcoServiceId;
	}


	public function getContent() {
		$timestamp = date('Y-m-d').'T'.date('H:i:s').'Z';

		return
<<<xml
<?xml version="1.0" encoding="ISO-8859-1"?>
<message id="{$this->getClickId()}">
	<sms type="mo">
		<retry count="0" max="0" />
		<destination messageid="{$this->getClickId()}">
			<address>
				<number type="abbreviated">{$this->getShortcode()}</number>
			</address>
		</destination>
		<source>
			<address>
				<number type="international">{$this->getMsisdn()}</number>
			</address>
		</source>
		<ud type="text">{$this->getText()}</ud>
		<scts>{$timestamp}</scts>
		<service-id>{$this->getTelcoServiceId()}</service-id>
	</sms>
	<from>wap</from>
	<to>WapAdapter::{$this->getTelcoServiceId()}</to>
	<proxy-id>{$this->getAffiliateId()}</proxy-id>
	<proxy-message-id>{$this->getClickId()}</proxy-message-id>
	<channel-type-id>{$this->getChannelTypeId()}</channel-type-id>
</message>
xml
		;
	}

	public function execute() {

		$caller = new ThirdPartySender();

		$caller->setSession($this->session);
		$caller->setShortcode($this->getShortcode());
		$caller->setIvrCode($this->getIvrCode());
		$caller->setValidateCode($this->getValidateCode());
		$caller->setText($this->getText());
		$caller->setAffiliateId($this->getAffiliateId());
		$caller->setClickId($this->getClickId());
		$caller->setServiceId($this->getTelcoServiceId());

		if (($code = $caller->execute()) != 200) {
			return $code;
		}

		return parent::execute();
	}

	public function getMethod() {
		return HttpClient::MTHD_POST;
	}

	public function getContentType() {
		return 'text/xml';
	}
}


class TmvMoSender extends CssMoSender {

	public function getUrl() {
		return new Url('http://103.246.17.89:4000/truemove-backend/sms');
	}
}

class TmhMoSender extends CssMoSender {

	public function getUrl() {
		return new Url('http://103.246.17.89:5000/truemoveh-backend/sms');
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

#		$url->setParam('telcoid', $this->session->getTelcoId());
		$url->setParam('telcoid', 1); # MobiFone
		$url->setParam('shortcode', $this->getShortcode());
		$url->setParam('keyword', $this->getText());
		$url->setParam('refid', $this->getClickId());
		

		return $url;
	}

	public function getMethod() {
		return HttpClient::MTHD_GET;
	}

}

class TmhMptAoc extends RedirectOperatorAoc {

	protected function getTelcoServiceId() {
		$session = $this->session;
		$connection = $session->getConnection();
		$query = $connection->createQuery(
<<<sql
		select
			oc.telco_service_id as 'telco-service-id'
		from wap.click c
			join wap.hit h using (hit_id)
			join wap.page p using (page_id)
			join wap.campaign n using (campaign_id)
			left join wap.click_tag t using (click_tag_id)
			left join message_service.outgoing_channel oc using (service_id)
		where c.click_id = ?
		and oc.telco_id = ?
		and oc.sms_action_id = ?
		limit 1
sql
		);

		$query->setInt(1, $this->getClickId());
		$query->setInt(2, 5);
		$query->setInt(3, 4);


		$query->open();

		$rows = $query->getResultArray();

#		$telcoServiceId = (count($rows) > 0 && $rows['telco-service-id'] != null)? $rows['telco-service-id']: '7104590700';
		$telcoServiceId = (count($rows) > 0 && $rows[0]['telco-service-id'] != null)? $rows[0]['telco-service-id']: '7104590700';

		return $telcoServiceId;
	}


	protected function getParamArray() {
		$array = array(
//			'id'		=> '71045907001',
//			'id'		=> "{$this->getTelcoServiceId()}1",
			'id'		=> $this->getTelcoServiceId().(($this->affiliateId == 227)? 2: 1),
			'keyword'	=> 'R',
			'refid'		=> $this->getClickId(),
//			'refid'		=> '220',
//			'media'		=> "111"
			'media'		=> "AB"
		);

		return $array;
	}

	public function execute() {
		$aocUrl = new Url($this->getAocUrl()->toString());
		$aocUrl->setParams($this->getParamArray(), true);
		Web::log("wapsub {$this->getTelcoId()} {$aocUrl->toString()}");
		parent::execute();
	}

	protected function getAocUrl() {
		return new Url('http://nextportal.hlifeplus.com/index/');
	}
}

class TmhMptMoSender extends MoSender {

	protected $msisdn;
	protected $telcoServiceId;

	public function __construct() {
		parent::__construct();

		$this->msisdn = null;
		$this->telcoServiceId = null;

		$this->timeout = 60;
		$this->channelTypeId = WapMedia::ctype_wap_aoc; 
	}

	public function setMsisdn($msisdn) {
		$this->msisdn = $msisdn;
	}

	public function setTelcoServiceId($telcoServiceId) {
		if ($this->telcoServiceId != $telcoServiceId) {
			$this->telcoServiceId = $telcoServiceId;

			$this->loadByTelcoServiceId();
		}
	}

	public function getMsisdn() {
		return $this->msisdn;
	}

	public function getTelcoServiceId() {
		return $this->telcoServiceId;
	}

	public function loadByTelcoServiceId() {
		$session = $this->session;
		$connection = $session->getConnection();
		$query = $connection->createQuery(
<<<sql
			select
				shortcode
			from message_service.outgoing_channel
			where telco_service_id = ?
			and telco_id = ?
			and sms_cat_id = ?
			limit 1
sql
		);

		$query->setString(1, $this->telcoServiceId);
		$query->setInt(2, WapMedia::telco_rmv);
		$query->setInt(3, WapMedia::scat_brc);

		$query->open();

		$rows = $query->getResultArray();

		if (count($rows) > 0) {
			$row = $rows[0];

			$this->shortcode = $row['shortcode'];
		}
		else
			return false;
	}

	public function loadByClickId() {
		$timestamp = array('current' => new DateTime());

		$timestamp['begin'] = clone $timestamp['current'];
		$timestamp['begin']->sub(new DateInterval('P1D'));

		$timestamp['end'] = clone $timestamp['current'];
		$timestamp['end']->add(new DateInterval('PT1H'));

		$times = array(
			'begin' => $timestamp['begin']->format('Y-m-d H:m:s.u'),
			'end'   => $timestamp['end']->format('Y-m-d H:m:s.u')
		);

		$session = $this->session;
		$connection = $session->getConnection();
		$query = $connection->createQuery(
<<<sql
			select
				oc.shortcode,
				ic.keyword as text,
				p.affiliate_id,
				p.page_id,
				
				s.msisdn,
				oc.telco_service_id
			from wap.click c
				join wap.hit h on h.sys_timestamp between '{$times['begin']}' and '{$times['end']}' and c.hit_id = h.hit_id
				join wap.session s on s.sys_timestamp between '{$times['begin']}' and '{$times['end']}' and h.session_id = s.session_id
				join wap.page p using (page_id)

				join wap.click_tag ct using (click_tag_id)
				join message_service.incoming_channel ic using (service_id, telco_id)
				join message_service.outgoing_channel oc using (service_id, telco_id)
			where c.sys_timestamp between '{$times['begin']}' and '{$times['end']}'
			and c.click_id = ?
			and ic.sms_action_id = 3
			and oc.sms_action_id = 11
			limit 1
sql
		);

//		$query->setInt(1, $this->getClickId());
		$query->setString(1, $this->getClickId());

		$query->open();

		$rows = $query->getResultArray();

		if (count($rows) > 0) {
			$row = $rows[0];

			$this->shortcode = $row['shortcode'];
			$this->text = $row['text'];
			$this->affiliateId = $row['affiliate_id'];
			$this->pageId = $row['page_id'];

			$this->msisdn = $row['msisdn'];
			$this->telcoServiceId = $row['telco_service_id'];

			return true;
		}
		else
			return false;
	}

	public function setClickId($clickId) {
		if ($this->clickId != $clickId) {
			parent::setClickId($clickId);

			$this->loadByClickId();
		}
	}

	public function getContent() {
#		$timestamp = date('Y-m-d').'T'.date('H:i:s').'Z';
		$timestamp = new DateTime();
		

		$xml = <<<xml
<?xml version="1.0" encoding="ISO-8859-1"?>
<message id="{$this->getClickId()}">
	<sms type="mo">
		<retry count="0" max="0" />
		<destination messageid="{$this->getClickId()}">
			<address>
				<number type="abbreviated">{$this->getShortcode()}</number>
			</address>
		</destination>
		<source>
			<address>
				<number type="international">{$this->getMsisdn()}</number>
			</address>
		</source>
		<ud type="text">{$this->getText()}</ud>
		<scts>{$timestamp->format('Y-m-d\TH:m:s\Z')}</scts>
		<service-id>{$this->getTelcoServiceId()}</service-id>
	</sms>
	<from>wap</from>
	<to>WapAdapter::{$this->getTelcoServiceId()}</to>
	<proxy-id>{$this->getAffiliateId()}</proxy-id>
	<proxy-message-id>{$this->getClickId()}</proxy-message-id>
	<channel-type-id>{$this->getChannelTypeId()}</channel-type-id>
</message>
xml
		;

//		echo $xml;
//		exit;

//		Web::log("TmhMptMoSender [xml-sender]: $xml");

//		return $xml;
		return preg_replace('/\s*\n\s*/', '', $xml);
	}

	public function execute() {
#		$content = $this->getContent();
#		echo $content;
#		exit;

		parent::execute();
	}

	public function getUrl() {
#		return new Url('http://27.254.153.206:4000/truemove-backend/sms'); # saraburi.moserv.mobi
		return new Url('http://103.7.56.50:4000/truemove-backend/sms'); # maehongson.moserv.mobi
	}

	public function getContentType() {
		return 'text/xml; charset=utf-8';
	}
}

