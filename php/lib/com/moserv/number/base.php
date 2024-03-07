<?php

class Base {

	public static function parseInt($code, $symline) {
//		$digits = str_split(strrev($code));

		$digits = array();
		for ($i = 0; $i < mb_strlen($code); $i++) {
			$digits[] = mb_substr($code, $i, 1);
		}
//		$digits = array_reverse($digits);
		
//		$base = strlen($symline);
		$base = mb_strlen($symline);
		$power = 1;
		$value = 0;

//		foreach ($digits as $digit) {
		foreach (array_reverse($digits) as $digit) {
//			$position = strpos($symline, $digit);
			$position = mb_strpos($symline, $digit);
			$value += $position * $power;

			$power *= $base;
		}

		return $value;
	}

	public static function parseBase($int, $symline, $digits = 5) {
		$base = mb_strlen($symline);
		$count = max(ceil(log($int, $base)), $digits);
		$power = 1;
		$chars = array();

		for ($index = 0; $index < $count; $index++) {
			$shift = (int)($int / $power);
			$position = $shift % $base;
			
//			$char = $symline[$position];
			$char = mb_substr($symline, $position, 1);

			$chars[] = $char;

			$power *= $base;
		}

		$code = implode('', array_reverse($chars));

		return $code;
	}

/*
	public static function parseDecimal($mod_code, $url_id, $symline) {

# from base 16

		$symline_hexdec = substr($symline, 52, 61). substr($symline, 0, 6);
		$base_hexdec	= strlen($symline_hexdec);

		$index_hexdex	= $mod_code % $base_hexdec;
		$char_hexdec	= $symline_hexdec[$index_hexdex];
		$value_hexdec	= floor($mod_code / $base_hexdec);

		while ($value_hexdec) {
			$index_hexdec = $value_hexdec % $base_hexdec;
			$value_hexdec = floor($value_hexdec / $base_hexdec);
			$char_hexdec  = $symline_hexdec[$index_hexdec].$char_hexdec;
		}

		$result_mod_code = $char_hexdec;

# from base 64	

		$base = strlen($symline);

		$index = $url_id % $base;
		$char  = $symline[$index];
		$value = floor($url_id / $base);
		
		while ($value) {
			$index = $value % $base;
			$value = floor($value/$base);
			$char  = $symline[$index].$char;
		}
		$result_url_id   = str_pad($char, 5, "a", STR_PAD_LEFT);

		return $result_mod_code.$result_url_id;
	}
*/
}
