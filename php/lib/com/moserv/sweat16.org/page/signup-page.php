<?php

require_once('com/moserv/sweat16/page/page.php');
require_once('com/moserv/sweat16/loader/artist-list-loader.php');

class SignupPage extends Page {

	protected function doLoad() {
		$capsule = parent::doLoad();

		$loader = new ArtistListLoader();

		$capsule['artist-list'] = $loader->execute();

		return $capsule;
	}
}
