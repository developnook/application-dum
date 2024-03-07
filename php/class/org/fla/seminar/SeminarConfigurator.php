<?php

namespace org\fla\seminar;

require_once('com/moserv/net/url.php');
require_once('com/moserv/number/base.php');
require_once('org/gnu/phpqrcode/phpqrcode.php');

use Base;
use QRcode;
use Url;
use SimpleXMLElement;

class SMC {
	const NUL_IMF = 0x00;
	const SVG_IMF = 0x01;
	const PNG_IMF = 0x02;


	const BASE_SHORT_URI	= 'https://fla.moserv.co.th/s/';
	const BASE_LONG_URI		= 'https://docs.google.com/forms/d/e/1FAIpQLSfFUOpJ3t0SJYRRYVk9ub1hKfq4R4LI-QDDALr9LaARjq8vMw/viewform?';
	const ALPHABETS			= 'zZyYxXwWvVuUtTsSrRqQpPoOnNmMlLkKjJiIhHgGfFeEdDcCbBaA9876543210';
	const KEY_DIGITS		= 4;
	const NUM_DIGITS		= 3;

	public static $committees = array(
		array('count' => 10, 'name' => 'จิรภัทร'),

		array('count' => 10, 'name' => 'สุทธิชัย'),
		array('count' => 10, 'name' => 'สวาสดิ์'),
		array('count' => 10, 'name' => 'กฤษฏ์'),
		array('count' => 10, 'name' => 'กวิน'),
		array('count' => 10, 'name' => 'ลัทธพล'),
		array('count' => 10, 'name' => 'สุชาติ'),
		array('count' => 10, 'name' => 'อ๊อด น้ำดี'),
		array('count' => 10, 'name' => 'สิทธิชัย'),
		array('count' => 10, 'name' => 'ชาญ'),
		array('count' => 10, 'name' => 'อรรถพล'),
		array('count' => 10, 'name' => 'มนันพัทธ์'),
		array('count' => 10, 'name' => 'เศรษฐพงศ์'),
		array('count' => 10, 'name' => 'ภัษ'),

		array('count' => 10, 'name' => 'โค้ชเจมส์'),
		array('count' => 10, 'name' => 'จอย วิลาสินี'),
		array('count' => 10, 'name' => 'หลิง พรพิมล'),
		array('count' => 10, 'name' => 'สมาคมแฟรนไชส์และไลเซนส์'),
		array('count' => 10, 'name' => 'ป๊อบ ปุญญาพร'),
		array('count' => 10, 'name' => 'เกียรติ เถ้าแก่ใหม่'),
		array('count' => 10, 'name' => 'แพร กวิสรา'),

		array('count' => 10, 'name' => 'วจีวิภาค'),
		array('count' => 10, 'name' => 'ปพิชญ์ชฎา'),
		array('count' => 10, 'name' => 'สารภี'),
		array('count' => 10, 'name' => 'โม ชิตสกุณ'),
	);

	public static $tickets = null;

	public function __construct() {

		if (SMC::$tickets == null) {

			SMC::$tickets = array();

			$hash = array();

			for ($ind = 0; $ind < count(SMC::$committees); $ind++) {
				$committee = SMC::$committees[$ind];

				$fr = $hash[$committee['name']] ?? 0;
				$to = $fr + $committee['count'];
				$hash[$committee['name']] = $to;

				for ($number = $fr; $number < $to; $number++) {
					SMC::$tickets[] = array(
						'number'	=> $number + 1,
						'id'		=> $ind,
						'name'		=> $committee['name']
					);
				}
			}
		}
	}
}

class SeminarImageConverter extends SMC {

	protected function getSvgImage($uri) {
		global $_SERVER;

		ob_start();
		QRcode::svg($uri, false, QR_ECLEVEL_L, 3, 2.5, false);
		$content = ob_get_clean();

		$svg = $xml = new SimpleXMLElement($content);

		$svg->defs->addChild('style', <<<css
			.cls-1 {fill:white; stroke:#9d9fa2; stroke-width:.14px;}
			.cls-2 {fill:#2e3192;}
			.cls-3 {fill:#231f20;}
			.cls-4 {fill:#ed1c24; stroke-width: 0px;}
css
		);

		$g = $svg->addChild('g');
		$g->addAttribute('transform', 'translate(37.5 37.5)');

		$circle = $g->addChild('circle');
		$circle->addAttribute('class', 'cls-1');
		$circle->addAttribute('cx', '7.5');
		$circle->addAttribute('cy', '7.5');
		$circle->addAttribute('r', '7.5');

		$image = $g->addChild('image');
		$data = base64_encode(file_get_contents("{$_SERVER['DOCUMENT_ROOT']}/image/fla.svg"));

		$image->addAttribute('xlink:href', "data:image/svg+xml;base64,{$data}", 'http://www.w3.org/1999/xlink');

		return $svg->asXML();
	}

	protected function getPngImage($uri) {
		global $_SERVER;

		$images = array();

		$qw = 330;
		$qh = 330;

		$lw = 200;
		$lh = 200;

		$dw = 50;
		$dh = 50;

		$images['canvas'] = imagecreatetruecolor($qw, $qh);
#		imagealphablending($images['canvas'], false);
#		imagesavealpha($images['canvas'], true);

		ob_start();
		QRcode::png($uri, null, QR_ECLEVEL_L, 10);
		$content = ob_get_clean();

		$images['qr'] = imagecreatefromstring($content);
		imagesavealpha($images['qr'], true);


		$images['logo'] = imagecreatefrompng("{$_SERVER['DOCUMENT_ROOT']}/image/fla-logo-circle-200x200.png");
		imagealphablending($images['logo'], true);

		imagecopyresampled($images['canvas'], $images['qr'], 0, 0, 0, 0, $qw, $qh, $qw, $qh);
		imagecopyresampled($images['canvas'], $images['logo'], ($qw - $dw) / 2, ($qh - $dh) / 2, 0, 0, $dw, $dh, $lw, $lh);
#		imagecopyresized($images['qr'], $images['logo'], 42.5, 42.5, 0, 0, 15, 15, 200, 200);

		ob_start();
		imagepng($images['canvas']);
		$data = ob_get_clean();

		return $data;
	}
}


class SeminarConfigurator extends SeminarImageConverter {


	public static $tickets;

	public function __construct() {
		parent::__construct();
	}

	protected function getQrImageContent($imageFormat = SMC::SVG_IMF) {
		$image = null;


		switch ($imageFormat) {
			case SeminarConfigurator::SVG_IMF:


			break;
		}

		return $image;
	}

	public function getTicket($index, $imageFormat = SMC::NUL_IMF) {
		global $_SERVER;

		if ($index < 0 || $index >= $this->getTicketCount()) {
			return null;
		}

		$url = new Url();
		$ticket = SMC::$tickets[$index];
		$code = Base::parseBase($index, SMC::ALPHABETS, SMC::KEY_DIGITS);
#		$uri = sprintf('%s%s', SMC::BASE_SHORT_URI, $code);
		$uri = sprintf('%s/s/%s', $url->toString(Url::TOK_PORT), $code);
		$svg = null;


		$newTicket = array(
			'id'		=> $index,
			'number'	=> sprintf('%0'.SMC::NUM_DIGITS.'d', $ticket['number']),
			'name'		=> $ticket['name'],
			'url'		=> new Url($uri)
		);

		if ($imageFormat == SMC::SVG_IMF) 
			$newTicket['svg'] = $svg = $this->getSvgImage($uri);

		if ($imageFormat == SMC::PNG_IMF) 
			$newTicket['png'] = $png = $this->getPngImage($uri);

		return $newTicket;
	}

	public function getTicketCount() {
		return count(SMC::$tickets);
	}

	public function getTickets($srcInd = 0, $dstInd = -1) {
		if ($dstInd < 0 || $dstInd < $srcInd) {
			$dstInd = $this->getTicketCount();
		}

		$tickets = array();

		for ($ind = $srcInd; $ind <= $dstInd; $ind++) {
			$ticket = $this->getTicket($ind);
			$tickets[] = $ticket;
		}

		return $tickets;
	}

	public function getFormUrl($code) {
		$index = Base::parseInt($code, SMC::ALPHABETS);

		if ($index < 0 || $index >= $this->getTicketCount()) {
			return null;
		}

		$ticket = SMC::$tickets[$index];
		$params = array(
			'usp'				=> 'pp_url',
			'entry.1291614968'	=> sprintf('%s - %0'.SMC::NUM_DIGITS.'d', $ticket['name'], $ticket['number'])
//			'entry.81051969'	=> sprintf('%0'.SMC::NUM_DIGITS.'d', $ticket['number'])
		);

		$uri = sprintf('%s%s', SMC::BASE_LONG_URI, http_build_query($params, null, '&', PHP_QUERY_RFC3986));

		return new Url($uri);
	}

	public function findIndex($name) {
		$found = false;
		$index = 0;

		while  (!$found && $index < count(SMC::$tickets)) {
			$ticket = SMC::$tickets[$index];
			$tokens = preg_split('/\s+/', $ticket['name']);

#			print_r($tokens);

			if ($tokens !== false && $tokens[0] === $name)
				$found = true;
			else
				$index++;
		}
#		exit;
		return ($found)? $index: false;
	}
}
