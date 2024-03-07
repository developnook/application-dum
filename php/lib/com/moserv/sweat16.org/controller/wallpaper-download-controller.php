<?php

require_once('com/moserv/net/url.php');
require_once('com/moserv/sweat16/controller/controller.php');


class WallpaperDownloadController extends Controller {

	protected function queryWallpaper($record) {
		$session = $this->getSession();
		$connection = $session->getConnection();
		$query = $connection->createQuery(
<<<sql
			select
				c.content_length as 'content-length',
				ct.content_type_value as 'content-type',
				concat(c.content_filename, '.', ct.file_ext) as 'content-filename',
				concat('/usr/project/portal/ysmt-s16s-portal/content/content-', lpad(c.content_id, 5, '0'), '.', ct.file_ext) as 'content-filepath',
				coalesce(s.downloads, 0) as downloads
			from sweat16.content c
				join sweat16.media_type mt using (media_type_id)
				join sweat16.content_type ct using (content_type_id)
				left join (
					select
						_cd.content_id,
						count(*) as downloads
					from sweat16.content_download _cd
						join sweat16.content _c using (content_id)
						join sweat16.media_type _mt using (media_type_id)
					where _cd.user_id = ?
					and _mt.media_type_name = 'Wallpaper'
					group by _cd.content_id
				) s using (content_id)
			where c.content_id = ?
			and mt.media_type_name = 'Wallpaper'
sql
		);

		$query->setInt(1, $record['user-id']);
		$query->setInt(2, $record['content-id']);

		$query->open();

		$rows = $query->getResultArray();


		if (count($rows) == 0) {
			return array(
				0,
				null,
				null,
				null,
				null,
				'ไม่พบ Wallpaper ที่ต้องการดาวน์โหลด'
			);
		}
		elseif ($rows[0]['downloads'] == 0) {

			return array(
				1,
				$rows[0]['content-filepath'],
				$rows[0]['content-filename'],
				$rows[0]['content-type'],
				$rows[0]['content-length'],
				null
			);
		}
		else {
			return array(
				0,
				$rows[0]['content-filepath'],
				$rows[0]['content-filename'],
				$rows[0]['content-type'],
				$rows[0]['content-length'],
				'ท่านได้ทำการดาวน์โหลด Wallpaper นี้ไปก่อนหน้านี้แล้ว'
			);
		}
	}


	protected function newWallpaperDownload($record) {
		$session = $this->getSession();
		$connection = $session->getConnection();
		$query = $connection->createQuery(
<<<sql
			insert into sweat16.content_download (
				user_id,
				content_id
			)
			values (
				?,
				?
			)
sql
		);

		$query->setInt(1, $record['user-id']);
		$query->setInt(2, $record['content-id']);

		$query->open();

		return $connection->lastId();
	}

	public function execute() {
		$session = $this->getSession();
		$params = $this->getInputParams();

		$record = array();


		list(
			$record['content-status'],
			$record['content-filepath'],
			$record['content-filename'],
			$record['content-type'],
			$record['content-length'],
			$record['content-error-message']
		) = $this->queryWallpaper(
			array(
				'user-id'	=> $session->getVar('user-id'),
				'content-id'	=> $params['content-id']
			)
		);

		if ($record['content-status'] == 1) {
			$record['content-download-id'] = $this->newWallpaperDownload(
				array(
					'user-id'	=> $session->getVar('user-id'),
					'content-id'	=> $params['content-id']
				)
			);
		}

		$this->setOutputParams($record);
	}
}

