<?php

#require_once('com/moserv/util/web.php');

class StreamingEx {

	protected $filepath;
	protected $mime;
	protected $filename;


	public function __construct() {
		$this->filepath = null;
		$this->filename = null;
		$this->mime = null;
		$this->buffsize = 2048000;

		$this->forceDownload = false;
	}

	public function setFilepath($filepath) {
		$this->filepath = $filepath;
	}

	public function setFilename($filename) {
		$this->filename = $filename;
	}

	public function setMime($mime) {
		$this->mime = $mime;
	}

	public function setForceDownload($forceDownload) {
		$this->forceDownload = $forceDownload;
	}

	public function execute() {
		global $_SERVER, $HTTP_SERVER_VARS;

		$filepath = $this->filepath;
		$filename = ($this->filename == null)? basename($filepath): $this->filename;
		$filesize = filesize($filepath);
		$buffsize = $this->buffsize;

		if (isset($_SERVER['HTTP_RANGE']) || isset($HTTP_SERVER_VARS['HTTP_RANGE'])) {
			$httpRange = (isset($_SERVER['HTTP_RANGE']))? $_SERVER['HTTP_RANGE']: $HTTP_SERVER_VARS['HTTP_RANGE'];
			$ranges = explode('-', substr($httpRange, strlen('bytes=')));

			//Now its time to check the ranges
			if ((intval($ranges[0]) >= intval($ranges[1]) && $ranges[1] != "" && $ranges[0] != "" ) || ($ranges[1] == "" && $ranges[0] == "")) {
				//Just serve the file normally request is not valid :( 
				$ranges[0] = 0;
				$ranges[1] = $filesize - 1;
			}
		}
		else { //The client dose not request HTTP_RANGE so just use the entire file
 			$ranges[0] = 0;
			$ranges[1] = $filesize - 1;
		}


		$start = $stop = 0;

		if ($ranges[0] === '') {
			$stop = $filesize - 1;
			$start = $filesize - intval($ranges[1]);
		}
		elseif ($ranges[1] === '') {
			$start = intval($ranges[0]);
			$stop = $filesize - 1;
		}
		else {
			$stop = intval($ranges[1]);
			$start = intval($ranges[0]);
		}

		$file = fopen($filepath, 'rb');

		fseek($file, $start, SEEK_SET);
		$start = ftell($file);

		fseek($file, $stop, SEEK_SET);
		$stop = ftell($file);


#		$length = $stop - $start;
		$length = $stop - $start + 1;


#		echo "$start and $stop and $length";
#		exit;


		if (isset($_SERVER['HTTP_RANGE']) || isset($HTTP_SERVER_VARS['HTTP_RANGE'])) {
			header('HTTP/1.0 206 Partial Content');
			header('Status: 206 Partial Content');
		}

		header('Accept-Ranges: bytes');
		header("Content-type: {$this->mime}");

		if ($this->forceDownload)
			header("Content-Disposition: attachment; filename=\"{$filename}\""); 

		header("Content-Range: bytes {$start}-{$stop}/{$filesize}");
#		header("Content-Length: " . ($filesize + 1));
#		header("Content-Length: " . ($filesize));
		header("Content-Length: {$length}");
		//Finally serve data and done ~!
		fseek($file, $start, SEEK_SET);

		ignore_user_abort(true);
		@set_time_limit(0);

		while (!(connection_aborted() || connection_status() == 1) && $length > 0) {
			$blocksize = min($length, $buffsize);

			echo fread($file, $blocksize);
			$length -= $blocksize;

			flush();
		}

		fclose($file);

		return max(0, $length);
	}
}


##$streaming = new StreamingEx();
##$streaming->setFilepath('/home/f0rud/Aalto Talk with Linus Torvalds [Full-length].mp4');
##$streaming->setMime('application/otect-stream');

