<?php


class Mailer {

	private $session;
	private $mq;
	private $producer;

	public function __construct($session) {
		$this->session = $session;

		$this->mq = $this->session->getMq();

		$queuename = "email-{$session->qn()}";
#		$this->producer = $this->mq->createProducer($queuename);
	}

	protected function getMailHeader() {
		$paths = $this->getPaths();

		ob_start();
		include("{$paths['conf']}/mail-header.inc.php");
		$header = ob_get_clean();

		return $header;
	}

	protected function getMailFooter() {
		$paths = $this->getPaths();

		ob_start();
		include("{$paths['conf']}/mail-footer.inc.php");
		$footer = ob_get_clean();

		return $footer;
	}

	protected function getMailHtml($body) {
		$header = $this->getMailHeader();
		$footer = $this->getMailFooter();

		return "{$header}{$body}{$footer}";
	}


	public function execute($record) {
		global $_SESSION;

		$capsule = array(
			'type'	  => $record['type'],
			'to'	  => $record['to'],
			'subject' => $record['subject'],
			'body'	  => $this->getMailHtml($record['body'])
		);

		if (!empty($record['attachments'])) {
			$capsule['attachments'] = $record['attachments'];
		}


		if (!empty($record['type']) && $record['type'] != 0) {
			$emailId = $this->saveEmail(
				array(
					'user-id'	=> (empty($_SESSION['user-id']))? -1: $_SESSION['user-id'],
					'type'		=> $capsule['type'],
					'to'		=> $capsule['to'],
					'subject'	=> $capsule['subject'],
					'body'		=> $capsule['body']
				)
			);
		}

		$this->producer->execute(json_encode($capsule), JSON_UNESCAPED_UNICODE);
#		$this->producer->execute(json_encode(
#			array(
#				'to'		=> $capsule['to'],
#				'subject'	=> $capsule['subject'],
#				'body'		=> $capsule['body'],
#			)
#		));

		return $emailId;
	}
/*
	public function execute($to, $subject, $body, $type = 1) {
		global $_SESSION;

		$capsule = array(
			'type'	  => $type,
			'to'	  => $to,
			'subject' => $subject,
			'body'	  => $this->getMailHtml($body)
		);

		$emailId = $this->saveEmail(
			array(
				'user-id'	=> (empty($_SESSION['user-id']))? -1: $_SESSION['user-id'],
				'type'		=> $capsule['type'],
				'to'		=> $capsule['to'],
				'subject'	=> $capsule['subject'],
				'body'		=> $capsule['body']
			)
		);

		$this->producer->execute(json_encode(
			array(
				'to'		=> $capsule['to'],
				'subject'	=> $capsule['subject'],
				'body'		=> $capsule['body']
			)
		));

		return $emailId;
	}
*/
	protected function getPaths() {
		global $_SERVER;

		$htdocs = realpath($_SERVER['DOCUMENT_ROOT']);
		$conf = dirname($htdocs).'/conf';

		return array(
			'htdocs'=> $htdocs,
			'conf'	=> $conf
		);
	}

	protected function saveEmail($record) {
		$session = $this->session;
		$connection = $session->getConnection();

		$query = $connection->createQuery(
<<<sql
			insert into {$session->db()}.email (
				email_type_id,
				user_id,
				mail_to,
				mail_subject,
				mail_body
			)
			values (?, ?, ?, ?, ?)
sql
		);

		$query->setInt(1, $record['type']);
		$query->setInt(2, $record['user-id']);
		$query->setString(3, $record['to']);
		$query->setString(4, $record['subject']);
		$query->setString(5, $record['body']);

		$query->open();

		return $connection->lastId();
	}
}


