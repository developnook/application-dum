<?php


class UserAddress {

	private $userAddressId;

	private $email;
	private $userId;

	private $name;
	private $surName;
	private $phone;
	private $subdistrictId;
	private $zipCode;


	protected function find() {
		$connection = $this->session()->getConnection();


		$query = $connection->createQuery(
<<<sql
			select
				user_address_id
			from sweat16.user_address
			where user_id = ?
			and email = ?
			and name = ?
			and sur_name = ?
			and phone = ?
			and subdistrict_id = ?
			and zip_code = ?
sql
		);

		$query->setInt(1, $this->userId);
		$query->setString(2, $this->email);
		$query->setString(3, $this->name);
		$query->setString(4, $this->surName);
		$query->setString(5, $this->phone);
		$query->setInt(6, $this->subdistrictId);
		$query->setString(7, $this->zip);
		
		$query->open();

		$rows = $query->getResultArray();

		return (count($rows) == 0)? 0: $rows[0]['user_address_id'];
	}

	protected function create() {
		$connection = $this->session()->getConnection();


		$query = $connection->createQuery(
<<<sql
			insert into sweat16.user_address (
				user_id,
				email,
				name,
				sur_name,
				phone,
				subdistrict_id,
				zip_code
			)
			values (?, ?, ?, ?, ?, ?, ?)
sql
		);

		$query->setInt(1, $this->userId);
		$query->setString(2, $this->email);
		$query->setString(3, $this->name);
		$query->setString(4, $this->surName);
		$query->setString(5, $this->phone);
		$query->setInt(6, $this->subdistrictId);
		$query->setString(7, $this->zip);
		
		$query->open();

		return $connection->lastId();

	}

	public function setEmail($email) {
		$this->email = $email;
	}

	public function setUserId($userId) {
		$this->userId = $userId;
	}

	public function setName($name) {
		$this->name = $name;
	}

	public function setSurName($surName) {
		$this->surName = $surName;
	}

	public function setPhone($phone) {
		$this->phone = $phone;
	}

	public function setSubdistrictId($subdistrictId) {
		$this->subdistrictId = $subdistrictId;
	}

	public function setZipCode($zipCode) {
		$this->zipCode = $zipCode;
	}

	public function getUserAddressId() {
		return $this->userAddressId;
	}

	public function upsert() {
		if (($userAddressId = $this->find()) == 0) {
			$userAddressId = $this->create();
		}

		$this->userAddressId = $userAddressId;

		return $userAddressId;
	}
}




