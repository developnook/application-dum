<?php
	require_once('com/moserv/ttt/config.php');
?>
<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="content-type" content="application/xhtml+xml; charset=utf-8" />
<meta name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=1.0; user-scalable=0;" />
<title><?php echo Page::$page->getTitle(); ?></title>
<?php
	Page::$page->includeStylesheets();
	Page::$page->includeJavascripts();
?>
</head>
<body onload="<?php echo Page::$page->getOnloadfunc(); ?>">
<?php
#	include('com/moserv/ttt/html/toolbar.php');
	include('com/moserv/ttt/html/panel.php');
?>
	<div class="main">
		<div style="height: 10px;"></div>
		<div id="nodMain" class="node">
