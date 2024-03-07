<?php

require_once('com/moserv/karbon/basis.php');


class Controller extends Basis {

	protected $params;

	public function __construct() {
		parent::__construct();

		$this->params = array();
	}

	public function execute() { }

	public function setInputParams($var) {

		if (!is_string($var) || ($params = json_decode($var, true)) == null) {
#		if (($params = json_decode($var, true)) == null) {
			$params = $var;
		}

		$this->params['input'] = $params;
	}

	public function getInputParams($json = false) {
		if ($json == true)
			return json_encode($this->params['input'], JSON_UNESCAPED_UNICODE);
		else
			return $this->params['input'];
	}

	public function getOutputParams($json = false) {
		if ($json == true)
			return json_encode($this->params['output'], JSON_UNESCAPED_UNICODE);
		else
			return $this->params['output'];
	}

	public function setOutputParams($var) {

		if (!is_string($var) || ($params = json_decode($var, true)) == null) {
#		if (($params = json_decode($var, true)) == null) {
			$params = $var;
		}

		$this->params['output'] = $params;
	}
}

