<?php

class Initializer {

	public static function execute() {
		global $_SERVER;

		$htdocs = realpath($_SERVER['DOCUMENT_ROOT']);
		$class = dirname($htdocs).'/class';

//		set_include_path(get_include_path().":{$class}");
		set_include_path("{$class}:".get_include_path());
	}
}


Initializer::execute();

