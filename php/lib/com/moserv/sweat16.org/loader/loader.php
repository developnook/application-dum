<?php

require_once('com/moserv/sweat16/base.php');


class Loader extends Base {

	protected $buffer = null;


	protected function doExecute($params) { }

	public function execute($params = null) {
		if ($this->buffer == null) {
			$this->buffer = $this->doExecute($params);
		}

		return $this->buffer;
	}
}
