<?php

use org\fla\seminar\SMC;
use org\fla\seminar\SeminarConfigurator;

	$config = new SeminarConfigurator();

		$index = intval($_REQUEST['index'], 10);

		$ticket = $config->getTicket($index, SMC::SVG_IMF);

		if ($ticket == null)
			header("HTTP/1.1 404 Not Found");
		else {
			header("Content-Type: image/svg+xml");

			echo $ticket['svg'];
		}
