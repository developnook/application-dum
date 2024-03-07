<?php

require_once('com/moserv/net/url.php');

use org\fla\seminar\SeminarConfigurator;

	file_put_contents('/tmp/mo.tmp', print_r($_SERVER, true), FILE_APPEND);

	$formats = array(
		'html5' => array(
			'content-type'	=> 'text/html',
			'doctype'		=> "<!DOCTYPE html>\n",
			'attrs'			=> 'lang="en"'
		),
		'xhtml' => array(
			'content-type'	=> "application/xhtml+xml",
			'doctype'		=> "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\"\n\t\"html://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">",
			'attrs'			=> "xmlns=\"http://www.w3.org/1999/xhtml\" lang=\"en\" xml:lang=\"en\""
		)
	);

	$format = $formats[(preg_match('/line-poker/i', $_SERVER['HTTP_USER_AGENT']))? 'html5': 'xhtml'];
	


	$url = new Url();
	$config = new SeminarConfigurator();

	if (($_REQUEST['index'] === '')) {
		$frInd = 0;
		$toInd = $config->getTicketCount() - 1;
	}
	else {
		$index = intval($_REQUEST['index'], 10);

		$frInd = $toInd = $index;
	}


	$tickets = array();

	for ($index = $frInd; $index <= $toInd; $index++) {
		$ticket = $config->getTicket($index);

		if ($ticket == null) {
			header("HTTP/1.1 404 Not Found");
			exit;
		}

		$tickets[] = $ticket;
	}

	header("Content-Type: {$format['content-type']}; charset=utf-8");
	echo $format['doctype'];
?>
<html <?php echo $format['attrs']; ?>>
<head>
<meta http-equiv="content-type" content="<?php echo $format['content-type']; ?>; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
<meta property="og:locale" content="th_TH" />
<meta property="og:site_name" content="Franchise Opportunity Day" />
<meta property="og:title" content="FLA: Franchise Opportunity Day" />
<meta property="og:type" content="website" />
<meta property="og:url" content="<?php echo $url->toString(); ?>" />
<meta property="og:image" content="<?php echo ($index == 100)? "{$url->toString(Url::TOK_PORT)}/x/{$index}":"{$url->toString(Url::TOK_PORT)}/image/fla-logo.png"; ?>" />
<meta property="og:image:width" content="<?php echo ($index == 100)? 330:1119; ?>"/>
<meta property="og:image:height" content="<?php echo ($index == 100)? 330:1119; ?>" />
<meta property="og:description" content="สมาคมแฟรนไชส์และไลเซนส์ เรียนเชิญเจ้าของธุรกิจแฟรนไชส์, นักธุรกิจที่มองหาโอกาสจากแฟรนไชส์, และซัพพลายเออร์ มาพบกันในงาน Franchise Opportunity Day" />
<title>FLA Committee Ticketing</title>
<style>
/* <![CDATA[ */

@import url('https://fonts.googleapis.com/css2?family=Lato:wght@700&display=swap');
@import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@400&display=swap');

* {
	box-sizing: border-box;
}

html, body {
	--qr-size: 200px;
	--icon-size: 35px;
	--caption-height: calc(var(--qr-size) / 4);
	--font-size: calc(var(--caption-height) / 3.2);


	width: 100%;
	height: 100%;
	margin: 0px;
	padding: 0px;
}



ul.qrlist {
	list-style-type: none;
	padding: 2rem;
	margin: 0px;
	display: grid;
	grid-template-columns: repeat(auto-fill, minmax(var(--qr-size), 1fr));
	grid-gap: 1rem;
	justify-items: center;
}

ul.qrlist > li {
	display: block;
	position: relative;
	padding: 0px;
	margin: 0px;
	height: calc(var(--caption-height) + var(--qr-size) + var(--caption-height));
	width: var(--qr-size);
}


ul.qrcode {

	border: 1px dashed gray;
	border-radius: 5px;

	list-style-type: none;
	padding: 0px;
	margin: 0px;
	position: absolute;
	display: grid;

	grid-template-rows:var(--caption-height) var(--qr-size) var(--caption-height);

	left: 50%;
	top: 50%;

	height: calc(var(--caption-height) + var(--qr-size) + var(--caption-height));
	width: var(--qr-size);

	transform: translate(-50%, -50%);

	container-name: qrbox;
	font-family: sarabun;
	font-size: var(--font-size);
}

ul.qrcode > li {
	padding: 0px;
	margin: 0px;
	display: block;
	position: relative;
	overflow: hidden;
}

ul.qrcode > li:not(:first-child):before {
	content: "";
	display: block;
	position: absolute;
	left: 50%;
	top: 0px;
	transform: translate(-50%, 0%);
	height: 1px;
	width: calc(100% - 20px);
	border-bottom: 1px dashed gray;
}

ul.qrcode > li:first-child > img {
	display: block;
	position: absolute;
	left: 22px;
	top: 50%;
	width: 40px;
	height: 40px;
	transform: translate(0%, -50%);
}

ul.qrcode > li:first-child > p {
	display: block;
	position: absolute;
	padding: 0px;
	margin: 0px;
	text-align: right;
	right: 23px;
	top: 50%;
	transform: translate(0%, -50%);
	font-family: Lato;
	text-shadow: 1px 1px 2px black;
}

ul.qrcode > li:first-child > p > span:first-child {
	font-size: 1.35rem;
	color: crimson;
}

ul.qrcode > li:first-child > p > span:last-child {
	position: relative;
	top: -4px;
	font-size: 0.75rem;
}

ul.qrcode > li:first-child > p > span:last-child > span:first-child{
	color: #fff;
}

ul.qrcode > li:first-child > p > span:last-child > span:last-child{
	color: blue;
}

ul.qrcode > li:nth-child(2) {
	background-position: center;
	background-repeat: no-repeat;
	background-size: cover;
}

ul.qrcode > li:nth-child(2) > img {
	width: var(--qr-size);
	height: var(--qr-size);
	border: none;
}
/*
ul.qrcode > li:nth-child(2) > img:last-child {
	border-radius: 50%;

	left: 50%;
	top: 50%;
	height: var(--icon-size);
	width: var(--icon-size);

	display: block;
	position: absolute;
	transform: translate(-50%, -50%);
}
*/
ul.qrcode > li:last-child > p {
	left: 50%;
	top: 50%;
	transform: translate(-50%, -50%);

	padding: 0px;
	margin: 0px;
	display: block;
	position: absolute;
	white-space: nowrap;
	/* background-color: pink; */
	vertical-align: middle;
	text-align: center;
}

ul.qrcode > li:last-child > p > span {
	position: relative;
}

ul.qrcode > li:last-child > p > span:first-child {
	top: +3px;
	color: black;
}

ul.qrcode > li:last-child > p > span:last-child {
	top: -3px;
}

ul.qrcode > li:last-child > p > span:first-child > span {
	display: inline-block;
	padding: 0px;
	margin: 0px;
	white-space: nowrap;
	overflow: hidden;
}

ul.qrcode > li:last-child > p > span:first-child > span:first-child {
	max-width: 135px;
	text-overflow: ellipsis;
}

ul.qrcode > li:last-child > p > span:last-child > a {
	font-size: 0.7rem;
	color: blue;
	text-decoration: none;
}

ul.qrcode > li:last-child > p > span:last-child > a:hover {
	text-decoration: underline;
}

/* ]]> */
</style>
</head>
<body>
<ul class="qrlist">
<?php
	$pname = '#';

	foreach ($tickets as $ticket) {
?>
	<li style="<?php echo ($pname == $ticket['name'])? '': 'grid-column-start: 1;'; ?>">
		<a href="<?php echo $ticket['url']->toString(); ?>">
			<ul class="qrcode">
				<li>
					<!--<img src="/image/fla-logo-tran.png" loading="lazy" />-->
					<img src="/image/fla.svg" loading="lazy" />
					<p><span>Franchise</span><br /><span><span>Opportunity</span> <span>Day</span></span></p>
				</li>
				<li>
					<img src="/i/<?php echo $ticket['id']; ?>" loading="lazy" />
					<!--<img src="/image/fla-logo-circle.png" loading="lazy" />-->
				</li>
				<li>
					<p>
						<span><span><?php echo $ticket['name']; ?></span><span>&nbsp;-&nbsp;</span><span><?php echo $ticket['number']; ?></span></span>
						<br />
						<span><a href="<?php echo $ticket['url']->toString(); ?>"><?php echo $ticket['url']->toString(); ?></a></span>
					</p>
				</li>
			</ul>
		</a>
	</li>
<?php
		$pname = $ticket['name'];
	}
?>
</ul>
</body>
</html>

