<?php

spl_autoload_register(function($name) {
	$path = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $name).'.php';

	if (($rpath = stream_resolve_include_path($path)) !== false) {
		require_once($rpath);
	}
});
