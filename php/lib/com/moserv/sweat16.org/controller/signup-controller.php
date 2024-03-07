<?php

require_once('com/moserv/net/url.php');
require_once('com/moserv/sweat16/controller/controller.php');


class SignupController extends Controller {

	const signup_new	= 0x00;
	const signup_exist_ac	= 0x01;
	const signup_exist_inac	= 0x02;

	private $name;
	private $surname;
	private $email;
	private $password;
	private $gender;
	private $phone;

	private $birthYear;
	private $birthMonth;
	private $birthDay;



	protected function queryUser($record) {
		$params = $this->getInputParams();
		$session = $this->getSession();
		$connection = $session->getConnection();
		$query = $connection->createQuery(
<<<sql
			select
				user_id,
				enabled
			from sweat16.user
			where email = ?;
sql
		);

		$query->setString(1, $record['email']);

		$query->open();

		$rows = $query->getResultArray();

		return (count($rows) == 0)? array(0, 0): array($rows[0]['user_id'], $rows[0]['enabled']);
	}


	protected function newUser($record) {
		$params = $this->getInputParams();

		$session = $this->getSession();
		$connection = $session->getConnection();
		$query = $connection->createQuery(
<<<sql
			insert into sweat16.user (
				email,
				password,
				phone,
				name,
				surname,
				gender,
				birth_year,
				birth_month,
				birth_day,
				enabled,
				activate_key
			)
			values (
				?,
				?,
				?,
				?,
				?,
				?,
				?,
				?,
				?,
				?,
				uuid()
			)
sql
		);

		$query->setString(1, $record['email']);
		$query->setString(2, $record['password']);
		$query->setString(3, $record['phone']);
		$query->setString(4, $record['name']);
		$query->setString(5, $record['sur-name']);
		$query->setInt(6, $record['gender']);
		$query->setInt(7, $record['birth-year']);
		$query->setInt(8, $record['birth-month']);
		$query->setInt(9, $record['birth-day']);
		$query->setInt(10, -1);

		$query->open();

		return array($connection->lastId(), -1);
	}

	protected function newArtistList($record) {
		$session = $this->getSession();
		$connection = $session->getConnection();
		$query = $connection->createQuery(
<<<sql
			insert into sweat16.user_artist (
				user_id,
				artist_id,
				rank
			)
			values (?, ?, ?)
sql
		);

		$userArtistIdList = array();

#		foreach ($record['artist-id-list'] as $artistId) {
		for ($ind = 0; $ind < count($record['artist-id-list']); $ind++) {
			$artistId = $record['artist-id-list'][$ind];

			$query->setInt(1, $record['user-id']);
			$query->setInt(2, $artistId);
			$query->setInt(3, $ind);

			$query->open();

			$userArtistIdList[] = $connection->lastId();
		}


		return $userArtistIdList;
	}


	protected function setActivateKey($record) {
		$session = $this->getSession();
		$connection = $session->getConnection();
		$mailer = $session->getMailer();


		$query = $connection->createQuery(
<<<sql
			select
				activate_key
			from sweat16.user
			where user_id = ?
			and enabled = -1
sql
		);

		$query->setInt(1, $record['user-id']);
		$query->open();

		$rows = $query->getResultArray();

		if (count($rows) == 0) {
			return null;
		}
		else {
			$url = new Url();
			$key = $rows[0]['activate_key'];
			$url->setPath("/signup/do-activate/?key={$key}");
			$link = $url->toString();

			$html =
<<<html
				<p>โปรดเปิดใช้ {$record['email']} กับเราโดยการกดที่ลิงค์ด้านล่างนี้</p>
				<a href="{$link}">{$link}</a>
html
			;

			$mailer->execute(
				array(
					'type'		=> 1,
					'to'		=> $record['email'],
					'subject'	=> "เปิดใช้ {$record['email']} กับเรา",
					'body'		=> $html
				)
			);

			return $key;
		}
	}

	public function execute() {
		$session = $this->getSession();
		$params = $this->getInputParams();

		$record = array();

		list($record['user-id'], $record['enabled']) = $this->queryUser(array('email' => $params['email']));


		if ($record['user-id'] == 0) {
			list($record['user-id'], $record['enabled']) = $this->newUser(
				array(
					'email' => $params['email'],
					'password'	=> $params['password'],
					'phone'		=> $params['phone'],
					'name'		=> $params['name'],
					'sur-name'	=> $params['sur-name'],
					'gender'	=> $params['gender'],
					'birth-year'	=> $params['birth-year'],
					'birth-month'	=> $params['birth-month'],
					'birth-day'	=> $params['birth-day']
				)
			);

			$record['user-artist-id-list'] = $this->newArtistList(
				array(
					'user-id'		=> $record['user-id'],
					'artist-id-list'	=> $params['artist-id-list']
				)
			);
		}

		if ($record['enabled'] == -1) {
			$record['key'] = $this->setActivateKey(
				array(
					'user-id' => $record['user-id'],
					'email'	  => $params['email']
				)
			);
		}

		$this->setOutputParams($record);
	}
}

