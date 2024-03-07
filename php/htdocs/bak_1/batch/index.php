<?php

use org\fla\seminar\SeminarConfigurator;


function display($fr, $to, $name, $nfr) {
#	printf("%03d => %03d, %3d tks -- %s\n", $fr, $to, $to - $fr + 1, $name);

	$nnfr = $nfr;

	for ($ind = $fr; $ind <= $to; $ind++) {
		printf("%s - %03d\n\thttps://moo.mn/qr/%03d\n\n", $name, $nnfr++, $ind);
	}

	printf("\n");
}

	$config = new SeminarConfigurator();

	$fr = $to = -1;
	$tfr = $tto = -1;
	$pname = '#';
	$hash = array();

	for ($ind = 0; $ind < $config->getTicketCount(); $ind++) {

		$ticket = $config->getTicket($ind, false);

		if ($pname != $ticket['name']) {
			if ($pname != '#')
				display($fr, $to, $pname, $tfr, $tto);

			$fr = $ind;
			$tfr = $ticket['number'];
		}

		$pname = $ticket['name'];
		$to = $ind;
		$tto = $ticket['number'];
	}
	
	display($fr, $to, $pname);
