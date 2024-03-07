<?php

class HereDoc {
	public static function single($text) {
		return preg_replace('/^\s*|\s*\n\s*/', ' ', $text);
	}

	public static function indent($text) {
		$lines = explode("\n", $text);
		$short = null;

		foreach ($lines as $line) {
			if (preg_match('/^(\s*)(.*)$/', $line, $select)) {
				list(, $indent, $content) = $select;

				if ($content != '' && ($short == null || mb_strlen($short) > mb_strlen($indent))) {
					$short = $indent;
				}
			}

		}

		for ($ind = 0; $ind < count($lines); $ind++) {
			$lines[$ind] = preg_replace("/^{$short}/", '', $lines[$ind]);
		}

		return implode("\n", $lines);
	}
}
