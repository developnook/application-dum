<?php

require_once('com/moserv/sql/connection.php');
require_once('com/moserv/net/session.php');
require_once('com/moserv/net/url.php');
require_once('com/moserv/net/redirector.php');
#require_once('com/moserv/net/basefactor.php');
require_once('com/moserv/number/base.php');

class ShortUrl {

	const hexs	= '0123456789abcdef'; # 16 partitions
	const alphabets = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
	#                  01234567890123456789012345678901234567890123456789012345678901

	public static $sites = array(
		'm.sabver.com'	=> 'tuu', # t-stone
		'm.kangped.com'	=> 'roo', # revolic
		'm.kodfin.com'	=> 'moo'  # moserv
	);

	private $session;

	private $surl;
	private $lurl;
	
#	private $tb_name;
#	private $field_id;


	public function __construct($session) {
		$this->session = $session;
	}


	protected function getSite() {
		$url = new Url();

		return (preg_match('/^(roo|tuu|moo)\.mn$/', $url->getHost(), $group))? $group[1]: 'tuu';
	}

	public function hit($surl) {

		$site = $this->getSite();

		if (preg_match('/^([0-9a-f])([0-9a-zA-Z]{5})$/', $surl, $tokens)) {

			list(, $first, $suffix) = $tokens;

#			$modCode = strpos('0123456789abcdef', $first);
			$modCode = strpos(ShortUrl::hexs, $first); # find partition no.

#			$baseFactor = new BaseFactor();
#			$surlId = $baseFactor->parseInt($suffix);
			$surlId = Base::parseInt($suffix, ShortUrl::alphabets);
		
			$connection = $this->session->getConnection();
			$query = $connection->createQuery(
<<<sql
				select
					long_url
				from wap.s{$site}
				where s{$site}_id = ?
				and mod_code = ?
sql
			);
	
			$query->setInt(1, $surlId);
			$query->setInt(2, $modCode);

			$query->open(false);

			$rows = $query->getResultArray();

			if (count($rows) > 0) {
				$lurl = $rows[0]['long_url'];
				$this->lurl = $lurl;

				$url = new Url($lurl);
				$url->redirect();

			} else {
				$url = new Url();
				$serverName = $url->getHost();
				
				echo
<<<html
					<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
					<html>
					<head>
						<title>404 Not Found</title>
					</head>
					<body>
					<h1>Not Found</h1>
					<p>The requested URL " http://$serverName/$surl " was not found on this server.</p>
					</body>
					</html>
html
				;

				exit;
			}
		}
	}

	public function register ($lurl) {

		$url = new Url($lurl);
		$host = $url->getHost();

		$site = $this->getSite();

		if (($site = ShortUrl::$sites[$host]) == null) {
			$site = 'tuu';
			$host = 'tuu.mn';
		}

#		$baseFactor = new BaseFactor();
#		$baseFactor->getModInfo($lurl, $md5Code, $modCode);
		$md5Code = md5($lurl);
		$modCode = gmp_strval(gmp_mod("0x{$md5Code}", strlen(ShortUrl::hexs)));

		if ($surl != $this->exists($lurl)) {
			echo $surl;
			return $surl;
		} 
		else if ($surl == $this->exists($lurl)) { 

#			echo "success!!\n\r";

			$connection = $this->session->getConnection();
			$query  = $connection->createQuery(
<<<sql
			insert into
				wap.s{$site} (
					mod_code,
					md5_code,
					long_url
				)
				values (
					?,
					?,
					?
				)
sql
			);

			$query->setInt(1, $modCode);
			$query->setString(2, $md5Code);
			$query->setString(3, $lurl);

			$query->open(false);

			$surlId = $connection->lastId();

#			$baseFactor = new BaseFactor();
#			$surl = $baseFactor->parseBase62($surlId);
			$surl = Base::parseBase($surlId, ShortUrl::alphabets);

			if ($surl != $this->exists($lurl)) {
			} 

			return $surl;
		}

	}


	public function exists($lurl) {

		$url = new Url();
		$host = $url->getHost();

#		$baseFactor = new BaseFactor();

		$site = $this->getSite();

		if ( ! preg_match('/m\.(kangped|sabver|kodfin)/', $lurl, $nurl) ) {
			$host = 'tuu.mn';
			$site = 'tuu';

		}

#		$baseFactor->getModInfo($lurl, $md5Code, $modCode);
		$md5Code = md5($lurl);
		$modCode = gmp_strval(gmp_mod("0x{$md5Code}", strlen(ShortUrl::hexs)));

		$connection = $this->session->getConnection();
		$query = $connection->createQuery(
<<<sql
			select
				s{$site}_id,
				mod_code
			from wap.s{$site}
			where mod_code = ?
			and md5_code = ?
			and long_url = ?
sql
		);

		$query->setInt(1, $modCode);
		$query->setString(2, $md5Code);
		$query->setString(3, $lurl);

		$query->open(false);
	
		$rows = $query->getResultArray();

		if (count($rows) > 0) {

			$row = $rows[0];
		
			$surlId = $row['s'.$site.'_id'];
			$modCode = $row['mod_code'];

#			$path = $baseFactor->parseDecimal($modCode, $surlId) ;
			$path = substr(ShortUrl::hexs, $modCode, 1) . Base::parseBase($surlId, ShortUrl::alphabets, 5);

			echo "http://{$host}/{$path}";

			return $surlId;
		}
		else {
			return false;
		}
	}

}