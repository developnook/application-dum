<?php

require_once('com/moserv/net/streaming-ex.php');
require_once('com/moserv/sweat16/servlet/servlet.php');
require_once('com/moserv/sweat16/controller/wallpaper-download-controller.php');

class WallpaperDownloadServlet extends Servlet {

	public function execute() {
		global $_REQUEST;
		global $_SESSION;

		$controller = new WallpaperDownloadController();

		$controller->setInputParams($_REQUEST);

		$controller->execute();

		$params = $controller->getOutputParams();


		if ($params['content-status'] == 1) {
			$streaming = new StreamingEx();
			$streaming->setFilepath($params['content-filepath']);
			$streaming->setFilename($params['content-filename']);
			$streaming->setMime($params['content-type']);

			if ($streaming->execute() == true) {

			}
		}
		else {
			$_SESSION['content-error-message'] = $params['content-error-message'];
			$this->redirect('/download/wallpaper/rejected');
		}
	}
}

