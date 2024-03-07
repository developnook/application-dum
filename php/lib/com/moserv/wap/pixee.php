<?php

require_once('com/moserv/net/url.php');
require_once('com/moserv/number/base.php');
require_once('com/moserv/security/rsa.php');

class Pixee {

	const PIXEE_CACHE_KEY = 'auto-increment-pixee-firing';
	const PIXEE_QUEUE_KEY = 'pixee-firing';

	protected $affiliateId;

	protected $session;
	protected $sysTimestamp;

	public function __construct($session) {
		$this->session = $session;
		$this->sysTimestamp = (new DateTime())->format('Y-m-d H:i:s.u');
	}

	protected function getPixeeId() {
		$connection = $this->session->getConnection();

#		$query = $connection->createQuery('
#			select
#				pixee_id,
#				url
#			from wap.pixee
#			where telco_id = ?
#			and enabled = 1
#			limit 1
#		');

#		if ($this->session->getMsisdn() == "66624873810") {
#			echo "{$this->affiliateId}\n";
#			exit;
#		}

		$query = $connection->createQuery(
<<<sql
			select
				a.affiliate_id,
				p.pixee_id,
				p.url as uri
			from wap.pixee p
				join wap.affiliate a using (affiliate_id)
			where (p.telco_id = ? or p.telco_id = -1)
			and p.enabled = 1
			order by (p.affiliate_id = ? && p.force_return = 1) desc, (p.telco_id = -1) desc
			limit 1
sql
		);

		$query->setInt(1, $this->session->getTelcoId());
		$query->setInt(2, (empty($this->affiliateId))? 0: $this->affiliateId);

		$query->open();

		$rows = $query->getResultArray();
		$record = null;

		if (count($rows) > 0) {
			$row = $rows[0];

			$affiliateId = $row['affiliate_id'];
			$pixeeId = $row['pixee_id'];
			$uri = $row['uri'];

			$record = array($affiliateId, $pixeeId, $uri);
		}
		else
			$record = array(1, 0, 'https://www.google.com');

		return $record;
	}

	public function setAffiliateId($affiliateId) {
		$this->affiliateId = $affiliateId;
	}

	protected function getPixeeFiringId() {
		$cache = $this->session->getCache();

		return $cache->inc(Pixee::PIXEE_CACHE_KEY);
	}

	protected function push($pixeeId, $pixeeFiringId, $uuid, $url) {
		$mq = $this->session->getMQ();

		$producer = $mq->createProducer(Pixee::PIXEE_QUEUE_KEY);

		$message = array(
			'uuid'			=> $uuid,
			'sys-timestamp'		=> $this->sysTimestamp,
			'hit-id'		=> $this->session->getHitId(),
			'pixee-id'		=> $pixeeId,
			'pixee-firing-id'	=> $pixeeFiringId,
			'url'			=> $url->toString()
		);

		$json = json_encode($message);
		$producer->execute($json);
	}


	public function execute($redirect = false) {
#	66945285427
		$url = new Url('http://www.google.com');

		list($affiliateId, $pixeeId, $uri) = $this->getPixeeId();
		$pixeeFiringId = 0;

		if ($pixeeId != 0) {
			$pixeeFiringId = $this->getPixeeFiringId();

			$hex128 = sprintf(
				"%04x%04x%04x%04x%s",
					mt_rand(0, 0xffff),
					mt_rand(0, 0xffff),
					mt_rand(0, 0xffff),
					mt_rand(0, 0xffff),
					Base::parseBase($pixeeFiringId, '0123456789abcdef', 16)
			);

			preg_match('/^([0-9a-f]{8})([0-9a-f]{4})([0-9a-f]{4})([0-9a-f]{4})([0-9a-f]{12})$/', $hex128, $digits);
			array_shift($digits);
			$uuid = implode('-', $digits);
			$url = new Url(
				str_replace(
					array(
						'${source-id}',
						'${pixee-firing-id}'
					),
					array(
						$affiliateId,
						$uuid
					),
					$uri
				)
			);
#			$url->setParam('pixee-firing-id', $uuid);

			$this->push($pixeeId, $pixeeFiringId, $uuid, $url);

			$capsule = array(
				'sys-timestamp'	=> $this->sysTimestamp,
				'url'		=> $url->toString(),
				'telco-id'	=> $this->session->getTelcoId(),
				'msisdn'	=> $this->session->getMsisdn()
			);

			$jsonized = json_encode($capsule);
			$filename = '/usr/project/certs/moserv-rsa-public.pem';
			$encrypted = Rsa::encryptByPublicKey($jsonized, "file://{$filename}");
			$encoded = base64_encode($encrypted);

			$redUrl = new Url('https://api.moserv.mobi/redirect/'.urlencode($encoded));

			if ($redirect == true) {
				$redUrl->redirect();
				exit;
			}
		}

		return array($pixeeId, $pixeeFiringId, $url->toString());
	}
}

