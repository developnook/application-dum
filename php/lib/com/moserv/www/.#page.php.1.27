<?php

require_once('com/moserv/net/url.php');
require_once('com/moserv/www/config.php');

class Page {

	const pg_domain = 'moserv.co.th';

	public static $page;
	public static $langs = array('en', 'th');

	private $cssFiles;
	private $jsFiles;
	private $lang;
	private $pageXml;
	private $textXml;
	private $title;
	private $xmlId;

	public function __construct($title = null) {
		self::$page = $this;

		$this->lang = $this->identifyLang();
		$this->textXml = null;
		$this->pageXml = null;
		$this->title = $title;
		$this->xmlId = null;
		
		$this->cssFiles = array();
#		$this->cssFiles[] = 'nanoscroller.css';
		$this->cssFiles[] = 'page.css';
		$this->cssFiles[] = 'hover.css';
		$this->cssFiles[] = $this->getPageName().'.css';

		$this->jsFiles = array();
#		$this->jsFiles[] = 'jquery-1.11.1.min.js';
#		$this->jsFiles[] = 'jquery.nanoscroller.min.js';
		$this->jsFiles[] = 'https://code.jquery.com/jquery-latest.min.js';
		$this->jsFiles[] = 'xhtml-document-write.js';
		$this->jsFiles[] = 'page.js';
		$this->jsFiles[] = $this->getPageName().'.js';
	}

	protected function identifyLang() {
		$url = new Url();
		
		return (preg_match('/^th\./', $url->getHost())? 'th': 'en');
	}
	
	public function begin($xmlId = null) {
		global $_SERVER;

		$url = new Url();

		if (!preg_match('/^(th|en)\./', $url->getHost())) {
			$url->setHost('en.moserv.co.th');
			$url->redirect();
		}

#		include_once('com/moserv/www/page-header.php');
		include_once("{$_SERVER['DOCUMENT_ROOT']}/../conf/template/page-header.php");

		if ($xmlId != null) {
			$this->xmlId = $xmlId;
#			include_once('com/moserv/www/page-header-sub.php');
			include_once("{$_SERVER['DOCUMENT_ROOT']}/../conf/template/page-header-sub.php");
		}
	}


	public function end() {
		global $_SERVER;

		if ($this->xmlId != null) {
#			include_once('com/moserv/www/page-footer-sub.php');
			include_once("{$_SERVER['DOCUMENT_ROOT']}/../conf/template/page-footer-sub.php");
		}

#		include_once('com/moserv/www/page-footer.php');
		include_once("{$_SERVER['DOCUMENT_ROOT']}/../conf/template/page-footer.php");
	}

	public function extend() {
		global $_SERVER;

		include_once("{$_SERVER['DOCUMENT_ROOT']}/../conf/template/page-source.php");
	}

	public function includeCss($filename) {
		if (array_search($filename, $this->cssFiles) === FALSE) {
			$this->cssFiles[] = $filename;
		}
	}
	
	public function includeJs($filename) {
		if (array_search($filename, $this->jsFiles) === FALSE) {
			$this->jsFiles[] = $filename;
		}
	}

	public function getCssFiles() {
		global $_SERVER;
		$filepaths = array();

		foreach ($this->cssFiles as $filename) {
			if (preg_match('|^https?://|', $filename)) {
				$filepaths[] = htmlspecialchars($filename, ENT_XHTML);
			}
			else {
				$filepath = "/css/{$filename}";
				$realpath = "{$_SERVER['DOCUMENT_ROOT']}{$filepath}";

				if (file_exists($realpath)) {
					$filepaths[] = $filepath;
				}
			}
		}

		return $filepaths;
	}
	
	public function getJsFiles() {
		global $_SERVER;
		$filepaths = array();

		foreach ($this->jsFiles as $filename) {

			if (preg_match('|^https?://|', $filename)) {
				$filepaths[] = htmlspecialchars($filename, ENT_XHTML);
			}
			else {
				$filepath = "/js/{$filename}";
				$realpath = "{$_SERVER['DOCUMENT_ROOT']}{$filepath}";

				if (file_exists($realpath)) {
					$filepaths[] = $filepath;
				}
			}
		}

		return $filepaths;
	}
	
	public function setLang($lang) {
		$this->lang = $lang;
	}
	
	public function getLang() {
		return $this->lang;
	}
	
	public function getAlternateLinks() {
		global $_SERVER;

		$url = new Url();
		$path = $url->getPath();
		preg_match('/^[^\.]+\.(.*)$/', $url->getHost(), $group);
		list(, $domain) = $group;

		$links = array();

		foreach (Page::$langs as $lang) {
			$host = "{$lang}.$domain";
			$url->setHost($host);

			$links[] = array(
				'cc'	=> $lang,
				'url'	=> $url->toString()
			);
		}

		return $links;
	}

	public function getLangPage($lang) {
		global $_SERVER;

		$url = new Url();
		$host = preg_replace('/^[a-z]{2}\./', "$lang.", $url->getHost());
		$url->setHost($host);

		return $url->toString();
	}
	
	public function getText($tag) {
		global $texts;
		
		return $texts[$tag]['caption-'. $this->lang];
	}

	public function getPageName() {
		global $_SERVER;

		return  basename(dirname($_SERVER['SCRIPT_NAME']));
	}

	protected function loadXml($prefix = null) {
		global $_SERVER;

		if ($prefix == null)
			$prefix = $this->getPageName();

		$lang = $this->lang;
#		$filename = "com/moserv/www/text-xml/{$prefix}-{$lang}.xml";
		$filename = "{$_SERVER['DOCUMENT_ROOT']}/../conf/text-xml/{$prefix}-{$lang}.xml";
#		echo $filename;
#		exit;
		$xmlText = file_get_contents($filename, true);

		$xml = new SimpleXMLElement($xmlText);

		return $xml;
	}

	public function getPageXml($xpath) {
		if ($this->pageXml == null) {
			$this->pageXml = $this->loadXml('page');
		}

		return $this->pageXml->xpath($xpath);
	}

	public function getTextXml($xpath) {
		if ($this->textXml == null) {
			$this->textXml = $this->loadXml();
		}

		return $this->textXml->xpath($xpath);
	}

	public function getLabel($key) {
		return $this->getPageXml("/page/texts/text[@key=\"{$key}\"]")[0];
	}

	public function getTitle() {
		$company = $this->getLabel('company');
		$title = $this->title;

		if ($title == null) {
			$pageName = $this->getPageName();

			$titles = null;

			try {
				$titles = $this->getTextXml("/{$pageName}/page-title");
			}
			catch (Exception $e) {
				$titles = null;
			}

			$title = (empty($titles))? '': $titles[0];
		}

		$fullTitle = sprintf('%s - %s', $company, $title);

		return $fullTitle;
	}

	public function getXmlId() {
		return $this->xmlId;
	}

	public function getMainTopic() {
		return $this->getPageXml("//menu[@id=\"{$this->xmlId}\"]")[0];
	}


	public function getOtherTopics() {
		return $this->getPageXml("//menu[@id=\"{$this->xmlId}\"]/../menu[@id!=\"{$this->xmlId}\"]");
	}
}

?>
