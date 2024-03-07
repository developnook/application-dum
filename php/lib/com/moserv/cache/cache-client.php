<?php

abstract class Cache {

	protected $handle;
	protected $last;


	public function __construct() {
		$this->handle = $this->newHandle();
		$this->last = 30 * 60;
	}

	abstract protected function newHandle();

	abstract protected function connect($host, $port, $timeout = 1);
	abstract protected function pconnect($host, $port, $timeout = 1);
	abstract protected function close();
	abstract protected function set($key, $value, $last = null);
	abstract protected function get($key);
	abstract protected function inc($key, $num = 1);

	protected function setLast($last) {
		$this->last = $last;
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


class MemcacheCache extends Cache {

	protected function newHandle() {
		return new Memcache();
	}


	protected function connect($host, $port, $timeout = 1) {
		return $this->handle->connect($host, $port, $timeout);
	}

	protected function pconnect($host, $port, $timeout = 1) {
		return $this->handle->pconnect($host, $port, $timeout);
	}

	protected function close() {
		return $this->handle->close();
	}

	protected function set($key, $value, $last = null) {
		if ($last == null)
			$last = $this->last;

		$this->handle->($key, $value, MEMCACHE_COMPRESSED, $last);
	}

	protected function get($key) {
		return $this->handle->get($key);
	}

	protected function inc($key, $num = 1) {
		return $this->handle->increment($key, $num);
	}
}

