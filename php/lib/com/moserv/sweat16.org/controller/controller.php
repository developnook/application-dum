<?php

require_once('com/moserv/sweat16/base.php');


class Controller extends Base {

	protected $params;

	public function __construct() {
		parent::__construct();

		$this->params = array();
	}

	public function execute() { }

	public function setInputParams($var) {

		if (($params = json_decode($var, true)) == null) {
			$params = $var;
		}

		$this->params['input'] = $params;
	}

	public function getInputParams($json = false) {
		if ($json == true)
			return json_encode($this->params['input']);
		else
			return $this->params['input'];
	}

	public function getOutputParams($json = false) {
		if ($json == true)
			return json_encode($this->params['output']);
		else
			return $this->params['output'];
	}

	public function setOutputParams($var) {

		if (($params = json_decode($var, true)) == null) {
			$params = $var;
		}

		$this->params['output'] = $params;
	}
}

