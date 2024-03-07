<?php
	require_once('com/moserv/console/access-control.php');

	$session = Page::$page->getSession();
	$userId = $session->getVar('userId');

	$loader = new FeatureLoader($session);

	$loader->setPortalId(Authenticator::port_b2b);
	$loader->setUserId($userId);

	$loader->load();
	$features = $loader->getRows();

?><?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="content-type" content="application/xhtml+xml; charset=utf-8" />
<meta name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=1.0;" />
<title><?php echo Page::$page->getTitle(); ?></title>
<link rel="icon" href="/favicon.ico" type="image/x-icon" />
<link rel="shortcut icon" href="/favicon.ico" type="image/x-icon" />
<link rel="stylesheet" type="text/css" href="/css/logo.css"></link>
<link rel="stylesheet" type="text/css" href="/css/page.css"></link>
<link rel="stylesheet" type="text/css" href="/css/progress.css"></link>
<link rel="stylesheet" type="text/css" href="/css/<?php echo Page::$page->getPageName(); ?>.css"></link>
<script type="text/javascript" src="/svgweb/src/svg.js"></script>
<script type="text/javascript" src="/js/iscroll.js"></script>
<script type="text/javascript" src="/js/ajax.js"></script>
<script type="text/javascript" src="/js/page.js"></script>
<script type="text/javascript" src="/js/<?php echo Page::$page->getPageName(); ?>.js"></script>
</head>
<body>
	<div id="pane" style="display: none; position: fixed; left: 0; top: 0; width: 100%; height: 100%; z-index: 5000; background-color: white; opacity: 0.5;" />
	<div id="facebookG" style="display: none; position: fixed; left: 50%; top: 50%; margin-top: -15px; margin-left: -21px; z-index: 6000;">
		<div id="blockG_1" class="facebook_blockG" />
		<div id="blockG_2" class="facebook_blockG" />
		<div id="blockG_3" class="facebook_blockG" />
	</div>	
	<div id="page-box" style="display: none;">
		<div id="menu-box">
			<div id="menu-toolbar">
				<table border="0" cellspacing="0" cellpadding="0">
				<tbody>
				<tr>
					<td align="left" valign="middle">
					</td>
				</tr>
				</tbody>
				</table>
			</div>
			<div id="menu-layout">
				<table cellpadding="0" cellspacing="0" border="0">
				<col />
				<col />
				<tbody>

		<?php
			foreach ($features as $feature) {
		?>
				<tr onclick="_page.goto('<?php echo $feature['path']; ?>');">
					<td class="menu-icon-cell" valign="middle" align="center">
						<div class="menu-icon">
							<div>
								<div />
								<div />
								<div />
							</div>
						</div>
					</td>
					<td class="menu-name-cell" valign="middle">
						<div class="menu-name"><?php echo $feature['name']; ?></div>
					</td>
				</tr>
		<?php
			}
		?>
<!--
				<tr onclick="_page.goto(_page.pg_browser);">
					<td class="menu-icon-cell" valign="middle" align="center">
						<div class="menu-icon">
							<div>
								<div />
								<div />
								<div />
							</div>
						</div>
					</td>
					<td class="menu-name-cell" valign="middle">
						<div class="menu-name">Browser</div>
					</td>
				</tr>
				<tr onclick="_page.goto(_page.pg_filter);">
					<td class="menu-icon-cell" valign="middle" align="center">
						<div class="menu-icon">
							<div />
						</div>
					</td>
					<td class="menu-name-cell" valign="middle">
						<div class="menu-name">Filter</div>
					</td>
				</tr>
				<tr onclick="_page.goto(_page.pg_password);">
					<td class="menu-icon-cell" valign="middle" align="center">
						<div class="menu-icon">
							<div />
						</div>
					</td>
					<td class="menu-name-cell" valign="middle">
						<div class="menu-name">Password</div>
					</td>
				</tr>
-->
				<tr onclick="_page.signout();">
					<td class="menu-icon-cell">
						<div class="menu-icon">
							<div />
						</div>
					</td>
					<td class="menu-name-cell" valign="middle">
						<div class="menu-name">Sign Out</div>
					</td>
				</tr>
				</tbody>
				</table>
			</div>
		</div>
		<div id="main-box">
			<div id="main-toolbar">
				<table border="0" cellspacing="0" cellpadding="0">
				<tbody>
				<tr>
					<td id="main-toolbar-left" align="left" valign="middle">
						<div id="setting" onclick="_page.slider.execute();">
							<div />
							<div />
							<div />
						</div>
					</td>
					<td id="main-toolbar-center" align="center" valign="middle" onclick="_page.toolbarSwitch();">
						<div id="logo">
							<?php include('com/moserv/console/b2b/logo.php'); ?>
						</div>
					</td>
					<td id="main-toolbar-right" valign="middle">
						<div id="signout" onclick="_page.signout();"></div>
					</td>
				</tr>
				</tbody>
				</table>
			</div>

			<div id="main-layout">
