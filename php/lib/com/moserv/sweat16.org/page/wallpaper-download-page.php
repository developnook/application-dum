<?php

require_once('com/moserv/sweat16/page/page.php');
require_once('com/moserv/sweat16/loader/wallpaper-list-loader.php');

class WallpaperDownloadPage extends Page {

	protected function doLoad() {
		$record = parent::doLoad();
		global $_REQUEST;

		$loader = new WallpaperListLoader();

		$record['wallpaper-list'] = array( 'rows' => $loader->execute($_REQUEST) );

		return $record;
	}
}
