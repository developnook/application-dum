<?php
	$content = file_get_contents("/usr/project/moserv-fla/php/htdocs/www/image/fla.svg");
#	$content = preg_replace("/\\s*(\n\r|\r|\n)\\s*/", "", $content);


	$xml = new SimpleXMLElement($content);
	$xml->registerXPathNamespace('svg', 'http://www.w3.org/2000/svg');

	$style = $xml->xpath('//svg:style');


#	$xml = simplexml_load_string($content);
#	$xml->registerXPathNamespace("svg", "http://www.w3.org/2000/svg");
	
#	$style = $xml->xpath("//svg:style");

//	echo $style[0][0];

//	$style[0][0] = "";

	echo $xml->asXML();


	print_r($xml);
