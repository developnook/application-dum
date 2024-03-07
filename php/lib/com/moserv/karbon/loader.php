<?php

require_once('com/moserv/karbon/basis.php');


class Loader extends Basis {

	protected $buffer = null;


	protected function doExecute($params) { }

	public function execute($params = null) {
		if ($this->buffer == null) {
			$this->buffer = $this->doExecute($params);
		}

		return $this->buffer;
	}
}
