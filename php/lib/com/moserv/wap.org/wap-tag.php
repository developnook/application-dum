<?php

class Attr {

	private $name;
	private $value;

	public function __construct() {
		$this->name = $this->value = '';
	}

	public function setName($name) {
		$this->name = $name;
	}

	public function setValue($value) {
		$this->value = $value;
	}

	public function toString() {
		return $this->name . '=' . htmlspecialchars($this->value, ENT_XML1);
	}

}


class Tag {
	protected $attrs;
	protected $name;
	protected $value;

	public function __construct() {
		$this->attrs = array();
	}

	public function clear() {
		$this->attrs = array();
	}

	public function setName($name) {
		$this->name = $name;
	}

	public function setValue($value) {
		$this->value = $value;
	}

	public function addAttr($attr) {
		$this->attrs[] = $attr;
	}

	public function getAttrs() {
		return $this->attrs;
	}

	public function toString() {
		$buffer = array();

		for ($index = 0; $index < count($attrs); $index++) {
			$attr = $attrs[$index];

			$buffer[] = $attr->toString();
		}

		$fname = $this->name;
		$fattr = implode(' ', $buffer);
		$fvalue = htmlspecialchars($this->value);

		return "<$fname $fattr>$fvalue</$fname>";
	}
}

class WapTag extends Tag {

	protected $br;

	public function __construct() {
		parent::__construct();

		$this->attrs[] = $this->br = new Attr();
		$this->br->setName('br');
		$this->br->setValue(0);
	}

	public function getBr() {
		return $this->br;
	}
}

?>
