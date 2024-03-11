<?php
header('Content-Type:application/xhtml+xml; charset=utf-8');
echo <<<xhtml
<?xml version="1.0" encoding="UTF-8"?>
xhtml
;

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html id="html" xmlns="http://www.w3.org/1999/xhtml" xmlns:svg="http-www.w3.org/2000/svg" xmlns:xlink="http-www.w3.org/1999/xlink" lang="en" xml:lang="en">
<head>
	<meta http-equiv="content-type" content="application/xhtml+xml; charset=utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />

	<title>DUM</title>
	<link rel="icon" type="image/x-icon" href="/dum/logo.svg" />

	<link href="css/font.css" rel="stylesheet" />
	<link href="css/style.css?v=<?= date('Y-m-d h:i:s') ?>" rel="stylesheet" />
	<link href="css/navbar.css?v=<?= date('Y-m-d h:i:s') ?>" rel="stylesheet" />
	<link href="css/footer.css?v=<?= date('Y-m-d h:i:s') ?>" rel="stylesheet" />

	<link href="css/home.css?v=<?= date('Y-m-d h:i:s') ?>" rel="stylesheet" />
	<link href="css/product.css?v=<?= date('Y-m-d h:i:s') ?>" rel="stylesheet" />
	<link href="css/knowledge.css?v=<?= date('Y-m-d h:i:s') ?>" rel="stylesheet" />
	<link href="css/charcoal.css?v=<?= date('Y-m-d h:i:s') ?>" rel="stylesheet" />
	<link href="css/review.css?v=<?= date('Y-m-d h:i:s') ?>" rel="stylesheet" />
	<link href="css/wheretobuy.css?v=<?= date('Y-m-d h:i:s') ?>" rel="stylesheet" />
	<link href="css/contact.css?v=<?= date('Y-m-d h:i:s') ?>" rel="stylesheet" />

	<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
	
	<!-- Jquery -->
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js" integrity="sha512-3gJwYpMe3QewGELv8k/BX9vcqhryRdzRMxVfq6ngyWXwo03GFEzjsUm8Q7RZcHPHksttq7/GFoxjCVUjkjvPdw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
	
	<script src="js/navbar.js" type="text/javascript"></script>
</head>
<body>
<div class="container-wrap">
	<div class="content-wrap">

<?php
include_once('configs.php');