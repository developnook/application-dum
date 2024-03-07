<?php

abstract class Cache {

	protected $handle;
	protected $expire;


	public function __construct() {
		$this->handle = $this->newHandle();
		$this->expire = 30 * 60;
	}

	abstract protected function newHandle();

	abstract public function connect($host, $port, $timeout = 1);
	abstract public function pconnect($host, $port, $timeout = 1);
	abstract public function close();
	abstract public function set($key, $value, $expire = null);
	abstract public function get($key);
	abstract public function inc($key, $num = 1, $default = 0, $expire = 0);
	abstract public function dec($key, $num = 1, $default = 0, $expire = 0);

	public function setExpire($expire) {
		$this->expire = $expire;
	}

	public static function create($word = 'memcache') {
		$cache = null;

		switch ($word) {
			case 'memcache':
				$cache = new MemcacheCache();
			break;

			default: 
				$cache = new MemcacheCache();
			break;
		}

		return $cache;
	}
}

//class CacheCluster {
//
//	protected $servers;
//
//	public function __construct() {
//		$this->servers = array();
//	}
//
//	public function addServer($host, $port, $word = 'memcache') {
//		$this->servers[] = array(
//			'host' => $host,
//			'port' => $port,
//			'word' => $word,
//
//			'cache' => null
//		);
//	}
//
//	protected function getServerIndex($key) {
//		$sum = 0;
//
//		for ($ind = 0; $ind < mb_strlen($key); $ind++) {
//			$ch = mb_substr($key, 1, 1);
//			$sum += $ch;
//		}
//
//		return $sum % count($this->servers);
//	}
//
//	protected function getCache($key) {
//		$ind = $this->getServerIndex($key);
//		$server = &$this->servers[$ind];
//
//		if ($server['cache'] === null) {
//			$server['cache'] = Cache::create($server['word']);
//			$server['cache']->pconnect($server['host'], $server['port']);
//		}
//
//		return $server['cache'];		
//	}
//
//	public function set($key, $value, $expire = null) {
//		$cache = $this->getCache($key);
//		$cache->set($key, $value, $expire);
//	}
//
//	abstract public function get($key);
//	abstract public function inc($key, $num = 1, $default = 0, $expire = 0);
//	abstract public function dec($key, $num = 1, $default = 0, $expire = 0);
//
//}


class MemcacheCache extends Cache {
	protected function newHandle() {
		return new Memcache();
	}

	public function connect($host, $port, $timeout = 1) {
		return $this->handle->connect($host, $port, $timeout);
	}

	public function pconnect($host, $port, $timeout = 1) {
		return $this->handle->pconnect($host, $port, $timeout);
	}

	public function close() {
		return $this->handle->close();
	}

	public function set($key, $value, $expire = null) {
		if ($expire == null)
			$expire = $this->expire;

		$this->handle->set($key, $value, MEMCACHE_COMPRESSED, $expire);
	}

	public function get($key) {
		return $this->handle->get($key);
	}

	public function inc($key, $num = 1, $default = 0, $expire = 0) {
		return $this->handle->increment($key, $num, $default, $expire);
	}

	public function dec($key, $num = 1, $default = 0, $expire = 0) {
		return $this->handle->decrement($key, $num, $default, $expire);
	}
}


