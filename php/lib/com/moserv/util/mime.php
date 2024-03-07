<?php

	function get_mime_type($fileName, $mimePath, $objLogger = null) {
		if (($ext = pathinfo($fileName, PATHINFO_EXTENSION)) == '') 
			return false;
		
		$content = file_get_contents($mimePath);
		$pattern = '/(?:^|\r?\n)[\t ]*([^\t\r\n #]+)(?:[\t ]+[^\r\n\t #]+)*?[\t ]+'.preg_quote($ext, '/').'(\s|$)/i';
	
		return (preg_match($pattern, $content, $matches))? $matches[1]:false;
	}

?>
