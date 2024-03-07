<?php

require_once('com/moserv/sweat16/loader/loader.php');


class WallpaperListLoader extends Loader {

	protected function doExecute($params = null) {
		$session = $this->getSession();
		$connection = $session->getConnection();

		$query = $connection->createQuery(
<<<sql
			select
				c.content_id as 'content-id',
				c.content_name_th as 'content-name',
				concat('/image/content/title-', lpad(c.content_id, 5, '0'), '.', ct.file_ext) as 'title-image',
				coalesce(s.counter, 0) as downloads
			from sweat16.content c
				join sweat16.media_type mt using (media_type_id)
				join sweat16.content_type ct using (content_type_id)
				left join (
					select
						_c.content_id,
						count(*) as counter
					from sweat16.content_download _cd
						join sweat16.content _c using (content_id)
						join sweat16.media_type _mt using (media_type_id)
					where _cd.user_id = ?
					and _c.enabled = 1
					and _mt.media_type_name = 'Wallpaper'
					group by _cd.content_id
				) s using (content_id)
			where c.enabled = 1
			and mt.media_type_name = 'Wallpaper'
			order by c.content_id
sql
		);

		$query->setInt(1, $session->getVar('user-id'));

		$query->open();

		$rows = $query->getResultArray();
		
		return $rows;
	}
}
