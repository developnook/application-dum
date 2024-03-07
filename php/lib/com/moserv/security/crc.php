<?php

class Crc {
	/*
	 * crc16 function from https://stackoverflow.com/questions/14018508/how-to-calculate-crc16-in-php
	 * RomKazanova, thank for code.
	 */

	public static function perform($data, $base = 16) {
		$crc = 0xffff;
		for ($i = 0; $i < strlen($data); $i++) {
			$x = (($crc >> 8) ^ ord($data[$i])) & 0xff;
			$x ^= $x >> 4;
			$crc = (($crc << 8) ^ ($x << 12) ^ ($x << 5) ^ $x) & 0xffff;
		}

		return $crc;
	}
}
