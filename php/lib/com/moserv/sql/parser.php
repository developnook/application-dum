<?php

require_once('com/moserv/sql/database.php');

class SqlParser {
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

	public static $_SQL_KEYWORDS = array(
		'select',
		'show',
		'describe',
		'explain',
		'insert',
		'update',
		'delete',
		'drop',
		'create'
	);

	private $sql;
	private $parsedSql;
	private $sqlType;
	private $tokens;

	protected $connection;

	public function __construct() {
		$this->params = array();
	}

	public function emptyArray(&$arr) {
		while (count($arr) > 0)
			array_pop($arr);
	}

	public function setSql($asql) {
		if ($this->sql != $asql) {
			$this->parsedSql = null;
			$this->sql = $asql;
			$this->execParse();
			$this->execCheckType();
		}
	}

	public function setInt($index, $value) {
		$this->tokens[($index * 2) - 1] = $value;
		$this->parsedSql = null;
	}

	public function setFloat($index, $value) {
		$this->tokens[($index * 2) - 1] = $value;
		$this->parsedSql = null;
	}

	public function setString($index, $value) {
		$db = $this->getDb();

//		if (!get_magic_quotes_gpc())
		if (!function_exists('get_magic_quotes_gpc') || !get_magic_quotes_gpc())
			$value = $db->escapeString($this->connection->handle, $value);

                $this->tokens[($index * 2) - 1] = "'{$value}'";
		$this->parsedSql = null;
        }

//	public function setBlob($index, $value) {
//		$content = file_get_contents($value);
//		$encode = gzencode($content);
//		$this->setString($index, $encode);
//	}

	protected function execParse() {
		$this->tokens = preg_split('/(\?)/', $this->sql, -1, PREG_SPLIT_DELIM_CAPTURE);
	}

	protected function execCheckType() {
		if (preg_match('/^[^a-zA-Z]*([a-zA-Z]+)/', $this->sql, $stmt) != 0) {
			$keyword = strtolower($stmt[1]);

			for ($index = 0; $index < count(self::$_SQL_KEYWORDS); $index++)
				if ($keyword == self::$_SQL_KEYWORDS[$index]) {
					$this->sqlType = $index;
					return;
				}
		}

		$this->sqlType = self::_SQL_UNKNOWN;
	}

	public function getParsedSql() {
		if ($this->tokens == null)
			return null;

		if ($this->parsedSql == null)
			$this->parsedSql = /**implode($this->tokens)*/ implode('', $this->tokens);

		return $this->parsedSql;
	}

	public function getSql() {
		return $this->sql;
	}

	public function getSqlType() {
		return $this->sqlType;
	}

	public function getDb() { }

	public function setConnection($connection) {
		$this->connection = $connection;
	}
}
