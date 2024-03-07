<?php
	class Image {

		const IDENTIFY = '/usr/bin/identify';
		const CONVERT = '/usr/bin/convert';

		public static function resize($src, $dst, $width, $height) {
			$identify = Image::IDENTIFY;
			$convert = Image::CONVERT;

			$output = $group = null;
			/*
			if (
				($output = shell_exec("$identify -format '%w:%h\\n' $src")) == null ||
				!preg_match('/([0-9]+):([0-9]+)/', $output, $group)
			) {
				return null;
			}

			$swidth = $group[1];
			$sheight = $group[2];
			*/

#			if (shell_exec("$convert $src -resize {$width}x{$height} $dst") == null) {
			$output = system("$convert \"$src\" -resize {$width}x{$height} \"$dst\"", $result);

			return $result;
		}
	}
?>
