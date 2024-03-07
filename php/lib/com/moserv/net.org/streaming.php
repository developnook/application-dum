<?php

require_once('com/moserv/log/logger.php');
require_once('com/moserv/util/web.php');

class Streaming {
	
	const ERROR_NONE       = 0x00;
	const ERROR_MIME       = 0x01;
	const ERROR_MIMEPATH   = 0x02;
	const ERROR_FILE       = 0x03;
	const ERROR_STREAM     = 0x04;
	const ERROR_FSEEK      = 0x05;

	private $caption;
	private $filePath;

	private $mimePath;

	private $mimeType;
	private $force;
	private $isRange;
	private $buffSize;

	private $headers;

	public function __construct($caption = 'streaming') {

		$this->caption = $caption;

		Logger::$logger->info("{$this->caption}: Construction Loaded");
		set_time_limit(60);
		$this->buffSize = 0;
		$this->mimePath = null;
		$this->mimeType = null;
		$this->isRange= false;
		$this->force = true;
		$this->headers = array();
	}

	public function setFilePath($filePath) {
		$this->filePath = $filePath;
	}

	public function getFilePath() {
		return $this->filePath;
	}

	public function setMimePath($mimePath) {
		$this->mimePath = $mimePath;
	}		

	public function getMimePath() {
		return $this->mimePath;
	}

	public function setRangeSupport($isRange) {
		$this->isRange = $isRange;
	}

	public function isRangeSupport() {
		return $this->isRange;
	}

	public function setForce($force) {
		$this->force = $force;
	}

	public function isForce() {
		return $this->force;
	}

	public function setBuffSize($buffSize) {
		$this->buffSize = $buffSize;
	}
	
	public function getBuffSize() {
		return $this->buffSize;
	}

	protected function getMimeType() {
		$mime = Web::getMimeType($this->filePath, $this->mimePath);

		return ($mime === false)? null: $mime;

#		if (($ext = pathinfo($this->filePath, PATHINFO_EXTENSION)) == '') 
#			return null;
#
#		$content = file_get_contents($this->mimePath);
#		$pattern = '/(?:^|\r?\n)[\t ]*([^\t\r\n #]+)(?:[\t ]+[^\r\n\t #]+)*?[\t ]+'.preg_quote($ext, '/').'(\s|$)/i';
#	
#		return (preg_match($pattern, $content, $matches))? $matches[1]: null;
	}

	public function execute() {
		if (($handle = fopen($this->filePath, 'rb')) == null) {
			Logger::$logger->error("{$this->caption}: Can't Open File !!");
			return self::ERROR_FILE;
		}

		$fileSize = filesize($this->filePath);

		if(($mimePath = $this->mimePath) == null || !file_exists($mimePath)) {
			Logger::$logger->error("{$this->caption}: Can't Found MimePath !!");
			return self::ERROR_MIMEPATH;
		}


		if (($mimeType = $this->mimeType) == null && ($mimeType = $this->getMimeType()) == null) {
			Logger::$logger->error("{$this->caption}: Can't Found MimeType !!");	
			return self::ERROR_MIME;
		}


		$pathinfo = pathinfo($this->filePath);
		$buffSize = ($this->buffSize > 0)? $this->buffSize : $fileSize;


		Logger::$logger->info("{$this->caption}: Start Download Streaming ...");			

		$needRangeEnable = ($this->isRange && !empty($_SERVER['SERVER_RANGE']));

		if (!$needRangeEnable && $this->force)
			header("content-disposition: attachment; filename=\"{$pathinfo['basename']}\"");

		if ($this->isRange && isset($_SERVER['HTTP_RANGE']) && preg_match('/^([a-z]+)=([0-9]+)-([0-9]+)$/i', $_SERVER['HTTP_RANGE'], $match)) {

			Logger::$logger->info("{$this->caption}: HTTP_RANGE : {$_SERVER['HTTP_RANGE']}");
			$startPos = $match[2];
			$endPos = $match[3];
			header('HTTP/1.1 206 Partial Content');
			header("Accept-Ranges: 0-$fileSize");
		}
		else {
			$startPos = 0;
			$endPos = $fileSize - 1;
		}

		$contentLength = $endPos - $startPos + 1;


		if (fseek($handle, $startPos) <> 0) {
			Logger::$logger->error("{$this->caption}: Can't moves the file pointer (can't fseek)!!");
			return self::ERROR_FSEEK;
		}


		#############################
		header("content-type: $mimeType");
		header("content-length: $contentLength");
		header("Content-Range: bytes $startPos-$endPos/$fileSize");
		#############################


		foreach ($this->headers as $headerName => $headerValue) {
			header("$headerName: $headerValue");
		}

		ob_end_clean();
		ob_start();

		$byteWrite = 0;

		while ($byteWrite < $contentLength) {
			$readSize = min($contentLength - $byteWrite, $buffSize); 

			$buffer = fread($handle, $readSize);		
			echo $buffer;
	
//				set_time_limit(0);				

			ob_flush();	# both lines will cause the termination
			flush();	# when client is disconnected.

			$byteWrite += strlen($buffer);
		}

		ob_end_flush();
		fclose($handle);
		Logger::$logger->info("{$this->caption}: Downloaded File {$pathinfo['basename']} Size : " . $byteWrite . " [Content-type: $mimeType]");

		return self::ERROR_NONE;			
	}

	public function addHeader($name, $value) {
		$this->headers[$name] = $value;
	}
}

