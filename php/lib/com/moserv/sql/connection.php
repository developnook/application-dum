<?php

require_once('com/moserv/cache/cache.php');
require_once('com/moserv/sql/database.php');
require_once('com/moserv/sql/parser.php');
require_once('com/moserv/log/logger.php');

class Connection {
	public static $lconn = null;
	public static $rconn = null;

	public $handle = null;
	public $db;
	public $pers;

	private $cache;
	private $defaultUseCache;

	protected $lockCounter;

	public function __construct($db, $pers = false) {
		$this->lockCounter = 0;

		$this->cache = null;
		$this->defaultUseCache = true;

		$this->db = $db;
		$this->pers = $pers;
		$this->init();
	}
	
	protected function init() {
		if ($this->pers) {
			$this->handle = $this->db->pconnect();
		}
		else {
			$this->handle = $this->db->connect();
		}

		if (!$this->handle) {
			die('Unable to connect database : ' . $this->db->error($this->handle));
		}
	}

	public function __destruct() {
		$this->db->close($this->handle);
	}

	public function open($sql) {
		$resultSet = $this->db->query($this->handle, $sql);

		if (!$resultSet) {
			if ($this->lockCounter > 0)
				$this->rollback();

//			die($this->db->error($this->handle) . "[$sql]");
			throw new Exception("{$this->db->error($this->handle)} [{$sql}]");
		}

		return $resultSet;
	}

	public function execSql($sql) {
		if (!$this->db->query($this->handle, $sql)) {
			if ($this->lockCounter > 0)
				$this->rollback();

//			die($this->db->error($this->handle) . "[$sql]");
			throw new Exception("{$this->db->error($this->handle)} [{$sql}]");
		}
	}

	public function createQuery($sql) {
		$query = new Query();
		$query->setConnection($this);
		$query->setSql($sql);

		return $query;
	}

	public function beginTransaction() {
		if ($this->lockCounter++ == 0) {
			$this->db->beginTransaction($this->handle);
		}
	}

	public function rollback() {
		$this->db->rollback($this->handle);

		$this->lockCounter = 0;
	}

	public function commit() {
		if ($this->lockCounter > 0) {
			$this->lockCounter--;

			if ($this->lockCounter == 0)
				$this->db->commit($this->handle);
		}
	}

	public function lastId() {
		return $this->db->lastId($this->handle);
	}

	public function doCache($sql, $resultArray, $last = 30 * 60) {
		if ($this->cache == null)
			return false;
		else {
			$key = md5($sql);
			$this->cache->set($key, $resultArray, $last);
			Logger::$logger->info("do cached: $key");

			return true;
		}
	}

	public function isCached($sql) {
		if ($this->cache == null) {
			return null;
		}
		else {
			$key = md5($sql);
			$resultArray = $this->cache->get($key);

			Logger::$logger->info("return cached: $key");

			return $resultArray;
		}
	}

	public function setCache($cache) {
		$this->cache = $cache;
	}

	public function setDefaultUseCache($defaultUseCache) {
		$this->defaultUseCache = $defaultUseCache;
	}

	public function isDefaultUseCache() {
		return $this->defaultUseCache;
	}
}


class RemoteConnection extends Connection {

	protected function init() {
		$this->handle = $this->db->pconnect();

		if (!$this->handle)
			die('Unable to connect database : ' . $this->db->error($this->handle));
	}
}


class Query extends SqlParser {
	const _RM_NONE		= 0x00;
	const _RM_ASSOC		= 0x01;
	const _RM_INDEX		= 0x02;

	const _DT_INT		= 0x00;
	const _DT_FLOAT		= 0x01;
	const _DT_BOOL		= 0x02;
	const _DT_STR		= 0x03;
	const _DT_DATE		= 0x04;
	const _DT_UNK		= -1;
	
	const _SQL_SELECT	= 0x00;
	const _SQL_SHOW		= 0x01;
	const _SQL_DESCRIBE	= 0x02;
	const _SQL_EXPLAIN	= 0x03;
	const _SQL_INSERT	= 0x04;
	const _SQL_UPDATE	= 0x05;
	const _SQL_DELETE	= 0x06;
	const _SQL_DROP		= 0x07;
	const _SQL_CREATE	= 0x08;
	const _SQL_UNKNOWN	= -1;


	private $fromCache = null;
	public $trace = false;

#	protected $fetchType = MYSQL_BOTH; // undefined for PHP 7
	protected $fetchType = 3;
	protected $resultSet;
	protected $resultArray;

	public $resultMode = 1;

	public function setSql($sql) {
		SqlParser::setSql($sql);
		$this->resultArray = null;
	}

	public function setInt($index, $value) {
		sqlParser::setInt($index, $value);
		$this->resultArray = null;
	}

	public function setFloat($index, $value) {
		sqlParser::setFloat($index, $value);
		$this->resultArray = null;
	}

	public function setString($index, $value) {
		sqlParser::setString($index, $value);
		$this->resultArray = null;
	}

#	public function setBlob($index, $value) {
#		sqlParser::setBlob($index, $value);
#		$this->resultArray = null;
#	}

	protected function createResultArray($resultSet = null) {
		if ($resultSet == null) {
			$resultSet = $this->resultSet;
		}

		$db = $this->getDb();
		$resultArray = array();
#		$numFields = $db->numFields($resultSet);
		$numFields = $db->numFields($this->connection->handle, $resultSet);

		for ($rowIndex = 0; $rowIndex < $this->getNumRows(); ++$rowIndex) {
			$row = array();

			for ($fieldIndex = 0; $fieldIndex < $numFields; $fieldIndex++) {
				$name = $db->fieldName($resultSet, $fieldIndex);
				$type = $db->fieldType($resultSet, $fieldIndex);
				$value = $db->result($resultSet, $rowIndex, $fieldIndex);


				//echo "\n$name:$type\n";

				switch ($db->dataType($type)) {
					case Database::_DT_INT:
						$value = (int)$value;
					break;

					case Database::_DT_FLOAT:
						$value = (float)$value;
					break;

					case Database::_DT_BOOL:
						$value = ($value !== 0 || $value === 't' || $value === 'true')? true: false;
					break;

#					case Database::_DT_BLOB:
#						if (($decode = @gzdecode($value)) !== FALSE) {
#							$value = $decode;
#						}
#					break;
				}

				if ($this->resultMode & self::_RM_ASSOC)
					$row[$name] = $value;
					
				if ($this->resultMode & self::_RM_INDEX)
					$row[$fieldIndex] = $value;
			}

			$resultArray[] = $row;
		}

		return $resultArray;
	}

	public function setResultMode($resultMode) {
		$this->resultMode = $resultMode;
	}
	
	public function getResultArray() {

		if ($this->resultArray == null) {
			$this->createResultArray();
		}

		return $this->resultArray;
	}

	public function isEmpty() {
		return ($this->getNumRows() == 0);
	}

	public function open($useCache = null, $last = 30 * 60) {
		if ($useCache === null) {
			$useCache = $this->connection->isDefaultUseCache();
		}

		$sqlType = $this->getSqlType();
		$parsedSql = $this->getParsedSql();
		$db = $this->getDb();

		if ($parsedSql == null) {
			$this->fromCache = null;

			return false;
		}
		else {
			if ($sqlType == Query::_SQL_SELECT) {

				if (!$useCache || ($resultArray = $this->connection->isCached($parsedSql)) == null) {

					$this->resultSet = $resultSet = $this->connection->open($parsedSql);
					$resultArray = $this->createResultArray($resultSet);
					$this->fromCache = false;

					if ($useCache)
						$this->connection->doCache($parsedSql, $resultArray, $last);
				}
				else
					$this->fromCache = true;

				$this->resultArray = $resultArray;
			}
			else
				$this->connection->execSql($parsedSql);


			return true;
		}
	}

	public function getNumRows() {
		$db = $this->getDb();
		return $db->numRows($this->resultSet);
	}

	public function getAffectedRows() {
		$db = $this->getDb();
		return $db->affectedRows($this->connection->handle, $this->resultSet);
	}

	public function getFirstValue() {
		$db = $this->getDb();
		return $db->result($this->resultSet, 0, 0);
	}

	public function getJson() {
		return json_encode($this->getResultArray());
	}

	public function getDb() {
		return $this->connection->db;
	}

	public function isFromCache() {
		return $this->fromCache;
	}
}

