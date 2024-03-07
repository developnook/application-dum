<?php

class Xml {

	public static $search = array('"', '&', '\'', '<', '>');
	public static $replace = array('&quot;', '&amp;', '&apos;', '&lt;', '&gt;');

	public static function escape($value) {
		return str_replace(Xml::$search, Xml::$replace, $value);
	}

}

class XmlAttr {

	private $name;
	private $value;

	public function __construct($name = null, $value = null) {
		$this->name = $name;
		$this->value = $value;
	}

	public function setName($name) {
		$this->name = $name;
	}


	public function setValue($value) {
		$this->value = $value;
	}

	public function toString() {
		if ($this->value == null)
			return '';
		else
			return sprintf('%s="%s"', $this->name, Xml::escape($this->value));
	}
}

class XmlAttrList {

	private $attrs;
	private $hash;

	public function __construct() {
		$this->attrs = array();
		$this->hash = array();
	}

	public function setAttr($name, $value) {
		if (array_key_exists($name, $this->hash))
			$this->hash[$name]->setValue($value);
		else
			$this->attrs[] = $this->hash[$name] = new XmlAttr($name, $value);
	}

	public function getAttr($name) {
		return (array_key_exists($name, $this->hash))? $this->hash[$name]: '';
	}

	public function toString() {
		$texts = array();

		$index = 0;
		while ($index < count($this->attrs)) {
			$attr = $this->attrs[$index++];

			if ($attr != null && ($text = $attr->toString()) != null)
				$texts[] = $text;
		}

		return implode(' ', $texts);
	}

	public function count() {
		return count($this->attrs);
	}
}

abstract class XmlTag {
	protected $name;

	public function __construct($name = '#') {
		$this->name = $name;
	}

	public function getName() {
		return $this->name;
	}

	public function setName($name) {
		$this->name = $name;
	}

	public abstract function toString();
}

class XmlTagList {
	private $tags;

	public function __construct() {
		$this->tags = array();
	}

	public function addTag($tag) {
		$this->tags[] = $tag;
	}

	public function getTag($index) {
		return ($index >= 0 && $index < count($this->tags))? $this->tags[$index]: null;
	}

	public function toString() {
		$texts = array();

		$index = 0;

		while ($index < count($this->tags)) {
			$tag = $this->tags[$index++];

			if ($tag != null)
				$texts[] = $tag->toString();
		}

		return implode('', $texts);
	}

	public function count() {
		return count($this->tags);
	}
}


class CustomTag extends XmlTag {

	protected $attrs;
	protected $tags;

	public function __construct($name = '#') {
		parent::__construct($name);

		$this->attrs = new XmlAttrList();
		$this->tags = new XmlTagList();
	}

	public function addTag($tag) {
		$this->tags->addTag($tag);
	}

	public function setAttr($attrName, $attrValue) {
		$this->attrs->setAttr($attrName, $attrValue);
	}

	public function getAttr($attrName) {
		return $this->attrs->getAttr($attrName);
	}

	public function toString() {
		$text = sprintf('<%s %s>%s</%s>', $this->name, $this->attrs->toString(), $this->tags->toString(), $this->name);

		return $text;
	}
}

class TextTag extends XmlTag {
	protected $text;

	public function __construct($text = null) {
		parent::__construct('#text');

		$this->text = $text;
	}

	public function setText($text) {
		$this->text = $text;
	}

	public function toString() {
		return Xml::escape($this->text);
	}
}

/*
$aaa = new CustomTag("aaa");
$bbb = new CustomTag("bbb");
$aaa->setAttr('color', 'blue');
$aaa->setAttr('value', '1');

$aaa->addTag($bbb);
$aaa->addTag($bbb);
$aaa->addTag(new TextTag("yes & yes"));

$bbb->setAttr("bold", "yes");
$bbb->addTag(new TextTag("กระโดดตบ"));

echo $aaa->toString() ."\n";
echo $bbb->toString() ."\n";
*/
?>
