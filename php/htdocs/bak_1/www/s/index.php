<?php

use org\fla\seminar\SeminarConfigurator;


	$code = $_REQUEST['code'];
	$config = new SeminarConfigurator();
	$url = $config->getFormUrl($code);


	if ($url == null)
		header("HTTP/1.1 404 Not Found");
	else {
		header("Location: {$url->toString()}");
	}

