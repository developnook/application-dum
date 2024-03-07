<?php

require_once('com/moserv/net/session.php');

class Dispatcher {

	const DISP_CACHE_KEY = 'auto-increment-dispatch-info';
	const DISP_QUEUE_KEY = 'dispatch-info';

	private $session;

	private $affiliateCode;
	private $dispatchId;

	private $pageId;
	private $cpId;
	private $dispatchCampaignId;
	private $weight;
	private $dispatchInfoId;

	public function __construct($session) {

		$this->session = $session;
		$this->clear();
	}

	public function setAffiliateCode($affiliateCode) {

		$this->affiliateCode = $affiliateCode;
	}

	public function setDispatchId($dispatchId){

		$this->dispatchId = $dispatchId;
	}


	public function execute() {

		$connection = $this->session->getConnection();


#		$query = $connection->createQuery(
#<<<sql
#			select
#				p.page_id,
#				dc.dispatch_campaign_id,
#				substr(c.shortcode, 2, 3) as cp_id,
#				dc.weight
#			from wap.dispatch d
#				join wap.dispatch_campaign dc using (dispatch_id)
#				join wap.campaign c using (campaign_id)
#				join wap.page p using (campaign_id)
#				join wap.affiliate a using (affiliate_id)
#			where d.dispatch_id = ?
#			and a.affiliate_code = ?
#			and c.enabled = 1
#			and p.enabled = 1
#			and dc.weight > 0
#sql
#		);

		$query = $connection->createQuery(
<<<sql
			select
				p.page_id,
				dc.dispatch_campaign_id,
				substr(c.shortcode, 2, 3) as cp_id,
				dc.weight
			from wap.dispatch d
				join wap.dispatch_campaign dc using (dispatch_id)
				join wap.campaign c using (campaign_id)
				join wap.page p using (campaign_id)
				join wap.affiliate a using (affiliate_id)
				left join wap.campaign_block b on b.campaign_block_id = (
					select
						campaign_block_id
					from wap.campaign_block
					where sys_date = current_date()
					and block_code = 8
					and campaign_id = c.campaign_id
					limit 1
				)

			where d.dispatch_id = ?
			and a.affiliate_code = ?
			and c.enabled = 1
			and p.enabled = 1
			and dc.weight > 0
			and b.campaign_block_id is null
sql
		);

		$query->setInt(1, $this->dispatchId);
		$query->setString(2, $this->affiliateCode);

#		$query->open(true, 60 * 5);
		$query->open(false);
		
		$rows = $query->getResultArray();

		if (count($rows) > 0) {
			$index = $this->getIndex($rows);
			$row = $rows[$index];

			$this->pageId = $row['page_id'];
			$this->cpId = $row['cp_id'];
			$this->dispatchCampaignId = $row['dispatch_campaign_id'];
			$this->weight = $row['weight'];

			$result = true;
		}
		else {
			$this->clear();

			$result = false;
		}

		$this->setNoCache();
		$this->save();

		return $result;
	}


	protected function setNoCache() {
#		header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
#		header("Cache-Control: post-check=0, pre-check=0", false);
#		header("Pragma: no-cache");
		header("Connection: close");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");
		header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
	}

	protected function getIndex($rows){

		$list = array();

		foreach ($rows as $index => $row) {
			while ($row['weight']-- > 0) {
				$list[] = $index;
			}
		}

		return $list[array_rand($list)];
	}


	protected function getDispInfoId() {
		$cache = $this->session->getCache();

		return $cache->inc(Pixee::DISP_CACHE_KEY);
	}

	protected function push() {
		$timestamp = (new DateTime())->format('Y-m-d H:i:s.u');
		$dispatchInfoId = $this->session->getCache()->inc(Dispatcher::DISP_CACHE_KEY);
		$producer = $this->session->getMQ()->createProducer(Dispatcher::DISP_QUEUE_KEY);

		$message = array(
			'dispatch-info-id'	=> $dispatchInfoId,
			'sys-timestamp'		=> $timestamp,
			'hit-id'		=> $this->session->getHitId(),
			'dispatch-campaign-id'	=> $this->dispatchCampaignId,
			'page-id'		=> $this->pageId,
			'dispatch-weight'	=> $this->weight,
			'hit-dispatch-id'	=> $this->dispatchId,
			'hit-affiliate-code'	=> $this->affiliateCode
		);

		$json = json_encode($message);
		$producer->execute($json);

		return $dispatchInfoId;
	}


	protected function save() {
/*	
		$connection = $this->session->getConnection();
		$query = $connection->createQuery(
<<<sql
			insert into wap.dispatch_info (
					hit_id,
					dispatch_campaign_id, 
					page_id,
					dispatch_weight, 
					hit_dispatch_id, 
					hit_affiliate_code
				) values (
					?,
					?,
					?,
					?,
					?,
					?	
			)
sql
		);

		$query->setInt(1, $this->session->getHitId());
		$query->setInt(2, $this->dispatchCampaignId);
		$query->setInt(3, $this->pageId);
		$query->setInt(4, $this->weight);
		$query->setString(5, $this->dispatchId);
		$query->setString(6, $this->affiliateCode);

		$query->open();
*/

#		$this->dispatchInfoId = $connection->lastId();
		$this->dispatchInfoId = $this->push();
	}

	public function clear() {
		
		$this->pageId = 0;
		$this->cpId = null;
		$this->dispatchCampaignId = 0;
		$this->weight = 0;
	}

	public function getPageId() {

		return $this->pageId;
	}


	public function getDispatchInfoId() {
		return $this->dispatchInfoId;
	}

	public function getCpId() {
		return $this->cpId;
	}
}

