<?php

require_once('com/moserv/net/url.php');
require_once('com/moserv/sweat16/controller/controller.php');


class PasswordRequestController extends Controller {

	protected function queryUser($record) {
		$session = $this->getSession();
		$connection = $session->getConnection();
		$query = $connection->createQuery(
<<<sql
			select
				u.user_id as 'user-id',
				coalesce(pr.password_reset_id, 0) as 'password-reset-id',
				u.enabled
			from sweat16.user u
				left join sweat16.password_reset pr on u.user_id = pr.as_user_id and pr.sys_timestamp > date_sub(current_timestamp(), interval 15 minute)
			where u.email = ?
			and (u.enabled = 1 or u.enabled = -1)
sql
		);

		$query->setString(1, $record['email']);

		$query->open();

		$rows = $query->getResultArray();

		return (count($rows) == 0)? array(0, 0, 0): array(
			$rows[0]['user-id'],
			$rows[0]['password-reset-id'],
			$rows[0]['enabled']
		);
	}

	protected function newPasswordReset($record) {
		$session = $this->getSession();
		$connection = $session->getConnection();
		$query = $connection->createQuery(
<<<sql
			insert into sweat16.password_reset (
				hit_id,
				email,
				as_user_id,
				reset_key
			)
			values (?, ?, ?, uuid())
sql
		);
		
		$query->setInt(1, $record['hit-id']);
		$query->setString(2, $record['email']);
		$query->setInt(3, $record['user-id']);

		$query->open();

		return $connection->lastId();
	}


	protected function queryResetKey($record) {
		$session = $this->getSession();
		$connection = $session->getConnection();
		$query = $connection->createQuery(
<<<sql
			select
				reset_key
			from sweat16.password_reset
			where password_reset_id = ?
sql
		);

		$query->setString(1, $record['password-reset-id']);

		$query->open();

		$rows = $query->getResultArray();

		return (count($rows) == 0)? null: $rows[0]['reset_key'];
	}

	public function execute() {
		$session = $this->getSession();
		$params = $this->getInputParams();

		$record = array();

		list($record['user-id'], $record['password-reset-id'], $record['enabled']) = $this->queryUser(
			array('email' => $params['email'])
		);

		if ($record['user-id'] != 0 && $record['password-reset-id'] == 0 && $record['enabled'] == 1) {
			$record['password-reset-id'] = $this->newPasswordReset(
				array(
					'hit-id'	=> $session->getHitId(),
					'email'		=> $params['email'],
					'user-id'	=> $record['user-id']
				)
			);
			$record['reset-key'] = $this->queryResetKey(array('password-reset-id' => $record['password-reset-id']));

			$url = new Url();
			$url->setPath("/password/do-reset/?key={$record['reset-key']}");
			$link = $url->toString();

			$html =
<<<html
				<p>กรุณาตั้งค่ารหัสผ่านใหม่โดยการกดลิงค์ด้านล่างนี้</p>
				<a href="{$link}">{$link}</a>
				<p>ลิงค์ดังกล่าวจะมีอายุเพียง 15 นาทีเท่านั้น</p>
html
			;

			$mailer = $session->getMailer();
			$mailer->execute(
				array(
					'type'		=> 2,
					'to'		=> $params['email'],
					'subject'	=> 'ตั้งค่ารหัสผ่านใหม่',
					'body'		=> $html
				)
			);
		}

		$this->setOutputParams($record);
	}
}

