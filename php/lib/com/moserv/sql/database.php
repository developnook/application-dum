<?php

abstract class Database {
	const _DT_INT		= 0x00;
	const _DT_FLOAT		= 0x01;
	const _DT_BOOL		= 0x02;
	const _DT_STR		= 0x03;
	const _DT_DATE		= 0x04;
#	const _DT_BLOB		= 0x05;
	const _DT_UNK		= -1;

	protected $name;
	protected $host;
	protected $port;
	protected $user;
	protected $passwd;

	public abstract function connect();
	public abstract function pconnect();
	public abstract function close($handle);
	public abstract function query($handle, $sql);
	public abstract function beginTransaction($handle);
	public abstract function rollback($handle);
	public abstract function commit($handle);
	public abstract function error($handle);
	public abstract function numFields($handle, $rs);
	public abstract function numRows($rs);
	public abstract function affectedRows($handle, $rs);
	public abstract function fieldName($rs, $field);
	public abstract function fieldType($rs, $field);
	public abstract function result($rs, $row, $field);
	public abstract function escapeString($handle, $value);
	public abstract function dataType($type);
	public function lastId($handle) { return -1; }

	public function __construct($name, $host, $port, $user, $passwd) {
		$this->name = $name;
		$this->host = $host;
		$this->port = $port;
		$this->user = $user;
		$this->passwd = $passwd;
	}
}

class MySql extends Database {
	public function connect() {
		$handle = mysql_connect("{$this->host}:{$this->port}", $this->user, $this->passwd, false, 128);

		mysql_set_charset('utf8mb4', $handle); # extra line for utf8 :P

		if (!$handle || !mysql_select_db($this->name, $handle))
			return null;

		return $handle;
	}

	public function pconnect() {
		$handle = mysql_pconnect("{$this->host}:{$this->port}", $this->user, $this->passwd);

		mysql_set_charset('utf8mb4', $handle); # extra line for utf8 :P

		if (!$handle || !mysql_select_db($this->name, $handle))
			return null;

		return $handle;

	}

	public function close($handle) {
		return mysql_close($handle);
	}

	public function query($handle, $sql) {
		return mysql_query($sql, $handle);
	}

	public function beginTransaction($handle) {
#		mysql_query('set autocommot = 0', $handle);
		mysql_query('start transaction', $handle);
	}

	public function rollback($handle) {
		mysql_query('rollback', $handle);
	}

	public function commit($handle) {
		mysql_query('commit', $handle);
	}

	public function error($handle) {
		return mysql_error();
	}

	public function numFields($handle, $rs) {
		return mysql_num_fields($rs);
	}

	public function numRows($rs) {
		return mysql_num_rows($rs);
	}

	public function affectedRows($handle, $rs) {
		return mysql_affected_rows($handle);
	}

	public function fieldName($rs, $field) {
		return mysql_field_name($rs, $field);
	}

	public function fieldType($rs, $field) {
		return mysql_field_type($rs, $field);
	}

	public function result($rs, $row, $field) {
		return mysql_result($rs, $row, $field);
	}

	public function escapeString($handle, $value) {
		return mysql_real_escape_string($value);
	}

	public function dataType($type) {
#		$result = /**Database::_DT_UNK*/$this->_DT_UNK;
		$result = Database::_DT_UNK;

		switch($type) {
			case 'int'	:  $result = Database::_DT_INT; break;
			case 'real'	:  $result = Database::_DT_FLOAT; break;
#			case 'blob'	:
#			case 'longblob'	:  $result = Database::_DT_BLOB; break;
			default: $result = Database::_DT_STR; break;
		}

		return $result;
	}

	public function lastId($handle) {
		return mysql_insert_id($handle);
	}
}


class MySqlEx extends Database {
	public function connect() {
#		$handle = mysql_connect("{$this->host}:{$this->port}", $this->user, $this->passwd, false, 128);
		$handle = mysqli_connect($this->host, $this->user, $this->passwd, $this->name);

#		mysql_set_charset('utf8', $handle); # extra line for utf8 :P
		mysqli_set_charset($handle, 'utf8mb4');

#		if (!$handle || !mysql_select_db($this->name, $handle))
#			return null;

		return $handle;
	}

	public function pconnect() {
#		$handle = mysql_pconnect("{$this->host}:{$this->port}", $this->user, $this->passwd);
		$handle = mysqli_connect("p:{$this->host}", $this->user, $this->passwd, $this->name);

#		mysql_set_charset('utf8', $handle); # extra line for utf8 :P
		mysqli_set_charset($handle, 'utf8mb4');

#		if (!$handle || !mysql_select_db($this->name, $handle))
#			return null;

		return $handle;

	}

	public function close($handle) {
#		return mysql_close($handle);
		return mysqli_close($handle);
	}

	public function query($handle, $sql) {
#		return mysql_query($sql, $handle);
		return mysqli_query($handle, $sql);
	}

	public function beginTransaction($handle) {
#		mysql_query('set autocommot = 0', $handle);
#		mysql_query('start transaction', $handle);
#		mysqli_query($handle, 'start transaction');
		mysqli_begin_transaction($handle);
	}

	public function rollback($handle) {
#		mysql_query('rollback', $handle);
#		mysqli_query($handle, 'rollback');
		mysqli_rollback($handle);
	}

	public function commit($handle) {
#		mysql_query('commit', $handle);
#		mysqli_query($handle, 'commit');
		mysqli_commit($handle);
	}

	public function error($handle) {
#		return mysql_error();
		return mysqli_error($handle);
	}

	public function numFields($handle, $rs) {
#		return mysql_num_fields($rs);
		return mysqli_field_count($handle);
	}

	public function numRows($rs) {
#		return mysql_num_rows($rs);
		return mysqli_num_rows($rs);
	}

	public function affectedRows($handle, $rs) {
#		return mysql_affected_rows($handle);
		return mysqli_affected_rows($handle);
	}

	public function fieldName($rs, $field) {
#		return mysql_field_name($rs, $field);
		$fieldInfo = mysqli_fetch_field_direct($rs, $field);
		return $fieldInfo->name;

	}

	public function fieldType($rs, $field) {
#		return mysql_field_type($rs, $field);
		return mysqli_fetch_field_direct($rs, $field)->type;
	}

	public function result($rs, $row, $field) {
#		return mysql_result($rs, $row, $field);

		$numrows = mysqli_num_rows($rs); 
		if ($numrows && $row <= ($numrows-1) && $row >=0) {
			mysqli_data_seek($rs, $row);
			$resrow = (is_numeric($field)) ? mysqli_fetch_row($rs) : mysqli_fetch_assoc($rs);
			if (isset($resrow[$field])){
				return $resrow[$field];
			}
		}

		return false;
	}

	public function escapeString($handle, $value) {
#		return mysql_real_escape_string($value);
		return mysqli_real_escape_string($handle, $value);
	}

	public function dataType($type) {
		$result = Database::_DT_UNK;

#		$mysql_data_type_hash = array(
#			1=>'tinyint',
#			2=>'smallint',
#			3=>'int',
#			4=>'float',
#			5=>'double',
#			7=>'timestamp',
#			8=>'bigint',
#			9=>'mediumint',
#			10=>'date',
#			11=>'time',
#			12=>'datetime',
#			13=>'year',
#			16=>'bit',
#			//252 is currently mapped to all text and blob types (MySQL 5.0.51a)
#			253=>'varchar',
#			254=>'char',
#			246=>'decimal'
#		);


		switch($type) {
			case 1	:
			case 2	:
			case 3	:
			case 8	:
			case 9	:
				$result = Database::_DT_INT;
			break;

			case 4	:
			case 5	:
			case 246:
				$result = Database::_DT_FLOAT;
			break;

			default:
				$result = Database::_DT_STR;
			break;
		}

		return $result;
	}

	public function lastId($handle) {
#		return mysql_insert_id($handle);
		return mysqli_insert_id($handle);
	}
}







class PostgreSQL extends Database {
	public function connect() {
		$handle = pg_connect("host={$this->host} dbname={$this->name} user={$this->user} password={$this->passwd}");

		if (!$handle)
			return null;

		return $handle;
	}

	public function pconnect() {
		$handle = pg_pconnect("host={$this->host} dbname={$this->name} user={$this->user} password={$this->passwd}");

		if (!$handle)
			return null;

		return $handle;
	}

	public function close($handle) {
		return pg_close($handle);
	}

	public function query($handle, $sql) {
		return pg_query($handle, $sql);
	}

	public function beginTransaction($handle) {
		pg_query($handle, 'begin work');
	}

	public function rollback($handle) {
		pg_query($handle, 'rollback');
	}

	public function commit($handle) {
		pg_query($handle, 'commit');
	}

	public function error($handle) {
		return pg_last_error($handle);
	}

	public function numFields($handle, $rs) {
		return pg_num_fields($rs);
	}

	public function numRows($rs) {
		return pg_num_rows($rs);
	}

	public function affectedRows($handle, $rs) {
		return pg_affected_rows($rs);
	}

	public function fieldName($rs, $field) {
		return pg_field_name($rs, $field);
	}

	public function fieldType($rs, $field) {
		return pg_field_type($rs, $field);
	}

	public function result($rs, $row, $field) {
		return pg_fetch_result($rs, $row, $field);
	}

	public function escapeString($handle, $value) {
		return pg_escape_string($value);
	}

	public function dataType($type) {
		$result = /**Database::_DT_UNK*/ $this->_DT_UNK;

		switch ($type) {
			case 'int'	:
			case 'int4'	:
			case 'int8'	: $result = Database::_DT_INT; break;
			case 'numeric'	: $result = Database::_DT_FLOAT; break;
			case 'varchar'	: $result = Database::_DT_STR; break;
#			case 'bool'	: $result = Database::_DT_BOOL; break;
			default		: $result = Database::_DT_STR; break;
		}

		return $result;
	}
}

?>
