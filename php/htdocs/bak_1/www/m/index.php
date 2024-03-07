<?php

require_once('com/moserv/net/url.php');

use Url;
#use org\fla\seminar\SeminarConfigurator;

	$formUrl = new Url('https://forms.gle/1UWM9xXiTc6WdDNT8');

	preg_match('/^bod-((\d{4})(\d{2})(\d{2}))$/', $_REQUEST['value'], $selects);

	list(, $date, $yyyy, $mm, $dd) = $selects;
	$url = new Url();
#	file_put_contents('/tmp/mo.tmp', print_r($_SERVER, true), FILE_APPEND);

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

	header("Content-Type: {$format['content-type']}; charset=utf-8");
	echo $format['doctype'];

	header("Refresh:0;url={$formUrl->toString()}");
?>
<html <?php echo $format['attrs']; ?>>
<head>
<meta http-equiv="content-type" content="<?php echo $format['content-type']; ?>; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
<meta http-equiv="refresh" content="0; url=<?php echo $formUrl->toString(); ?>" />
<meta property="og:locale" content="th_TH" />
<meta property="og:site_name" content="Business Open Day" />
<meta property="og:title" content="BOD: Business Open Day" />
<meta property="og:type" content="website" />
<meta property="og:url" content="<?php echo "{$url->toString()}"; ?>" />
<meta property="og:image" content="<?php echo "{$url->toString(Url::TOK_PORT)}/image/heritage/poker-20231212.png"; ?>" />
<meta property="og:image:width" content="1039" />
<meta property="og:image:height" content="526" />
<meta property="og:description" content="BNI Heritage BOD: เรียนเชิญเข้าร่วมประชุมกับผู้ประกอบการมืออาชีพ และ ร่วมสร้างโอกาสทางธุรกิจกับเราไปอย่างไร้ขีดจำกัด!" />
<title>BNI Heritage BOD</title>
<?php if (!$showqr) { ?>
<script type="text/javascript">
<![CDATA[
document.addEventListener('DOMContentLoaded', event => {
	location.href = "<?php echo $formUrl->toString(); ?>";
});

]]>
</script>
<?php } ?>
</head>
<body>
<div>
	<p>กรุณารอสักครู่....</p>
</div>
</body>
</html>

