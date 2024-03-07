<?php

require_once('org/fla/autoloader.php');

use org\fla\seminar\SeminarConfigurator;


	$config = new SeminarConfigurator();

	echo $config->getTicketCount();


	for ($ind = 0; $ind < $config->getTicketCount(); $ind++) {
		$ticket = $config->getTicket($ind, false);

		print_r($ticket);
	}

