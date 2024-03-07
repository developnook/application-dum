<?

require_once('com/moserv/net/session.php');
require_once('com/moserv/sql/connection.php');
# require_once('com/moserv/net/url.php');
# require_once('com/moserv/net/redirector.php');

class Compressor {

}


class File {
	
	private $session;

	public function __construct($session) {
		 $this->session = $session;

	}

	public function saveToDb($path, $extName = true, $cmpId = 1) {

		if (!file_exists($path)) {

			return 0;
		}

		$info = pathinfo($path);

		$filename	= $info['filename'];
		$extName	= ($ext === true)? $info['extension']: $extName;
		$content	= file_get_contents($path);
		$md5		= md5($content);
		$size		= filesize($path);

		$connection	= $this->session->getConnection();

		$query = $connection->createQuery(
<<<sql
			insert into media.file (
				filename,
				md5,
				content,
				ext_id,
				size,
				cmp_id
			)
			select
				x.filename,
				x.md5,
				x.content,
				coalesce(e.ext_name, -1) as ext_id,
				x.size,
				x.cmp_id
			from (
				select
					? as filename,
					? as md5,
					? as content,
					? as ext_name,
					? as size,
					? as cmp_id
				from dual
			) x
				left join media.extension e using (ext_name)
sql
		);

		$query->setString(1, $filename);
		$query->setString(2, $md5);
		$query->setString(3, $content);
		$query->setInt(4, $extName);
		$query->setInt(5, $size);
		$query->setInt(6, $cmpId);

		$query->open();

		return array(
			'file-id'	=> $connection->lastId(),
			'filename'	=> $filename,
			'md5'		=> $md5,
			'content'	=> $content,
			'extension'	=> $extension,
			'size'		=> $size,
			'cmp-id'	=> $cmpId
		);
	}

	

	public function loadFromDb($fileId) {

		$connection	= $this->session->getConnection();

		$query		= $connection->createQuery(
<<<sql
			select
				content
			from media.file
			where file_id = ?
sql
		);

		$query->setInt(1, $fileId);

		$query->open();

		$rows = $query->getResultArray();

		if (count($rows) > 0) {
			$row = $rows[0];

			$content = $row['content'];
		}
		
		return array(
			'file-id'	=> $fileId,
			'filename'	=> $filename,
			'md5'		=> $md5,
			'content'	=> $content,
			'extension'	=> $extension,
			'size'		=> $size,
			'cmp-id'	=> $cmpId
		);
	}

	

}
