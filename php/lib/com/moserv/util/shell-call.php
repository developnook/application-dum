<?php
	require_once('com/moserv/net/url.php');

	$filepath = $argv[1];
	$_SERVER = json_decode($argv[2], true);
	$url = new Url($argv[3]);
	$_REQUEST = $url->getParams();

	require $filepath;
