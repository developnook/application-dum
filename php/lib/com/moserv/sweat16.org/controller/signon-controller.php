<?php

require_once('com/moserv/sweat16/controller/controller.php');


class SignonController extends Controller {


	protected function saveAuthen($record) {
		$params = $this->getInputParams();
		$session = $this->getSession();
		$connection = $session->getConnection();
		$query = $connection->createQuery(
<<<sql
			insert into sweat16.authen (
				hit_id,
				user_id,
				try_email,
				try_password
			)
			values (
				?,
				?,
				?,
				?
			)
sql
		);

		$query->setInt(1, $session->getHitId());
		$query->setInt(2, ($record == null)? -1: $record['user-id']);
		$query->setString(3, $params['email']);
		$query->setString(4, $params['password']);

		$query->open();

		$authenId = $connection->lastId();

		if ($record == null) {
			$newRecord = array(
				'user-id'	=> -1,
				'authen-id'	=> $authenId,
				'name'		=> '',
				'user-email'	=> '',
				'enabled'	=> 0
			);
		}
		else {
			$newRecord = $record;
			$newRecord['authen-id'] = $authenId;
			$newRecord['user-email'] = $params['email'];
		}

		return $newRecord;
	}

	protected function queryUser() {
		$params = $this->getInputParams();
		$session = $this->getSession();
		$connection = $session->getConnection();
		$query = $connection->createQuery(
<<<sql
			select
				user_id,
				name,
				enabled
			from sweat16.user
			where email = ?
			and password = ?
			and (enabled = 1 or enabled = -1)
sql
		);

		$query->setString(1, $params['email']);
		$query->setString(2, $params['password']);

		$query->open();

		$rows = $query->getResultArray();

		$record = (count($rows) == 0)? null: array(
			'user-id'	=> $rows[0]['user_id'],
			'name'		=> $rows[0]['name'],
			'enabled'	=> $rows[0]['enabled']
		);

		return $record;
	}

	public function execute() {

		$record = $this->queryUser();
		$record = $this->saveAuthen($record);

		if ($record != null && $record['user-id'] != -1 && $record['enabled'] != -1) {
			$this->session->setVar('user-id', $record['user-id']);
			$this->session->setVar('name', $record['name']);
			$this->session->setVar('user-email', $record['user-email']);
		}

		$this->setOutputParams($record);
	}
}

