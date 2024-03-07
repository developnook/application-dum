<?php

require_once('com/moserv/sweat16/loader/loader.php');

class ArtistListLoader extends Loader {

	protected function doExecute($params = null) {
		$session = $this->getSession();
		$connection = $session->getConnection();

		$query = $connection->createQuery(
<<<sql
			select
				artist_id	as 'artist-id',
				nickname_th	as 'nickname',
				nickname_en	as 'nickname-en',
				nickname_th	as 'nickname-th',
				nickname_jp	as 'nickname-jp',
				enabled
			from sweat16.artist
			order by nickname
sql
		);

		$query->open();

		$rows = $query->getResultArray();

		foreach ($rows as &$row) {
			$row['image'] = sprintf('/image/idol/idol-%02d.png', $row['artist-id']);
		}

		return $rows;
	}
}
