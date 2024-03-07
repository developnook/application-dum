<?php

require_once('com/moserv/net/url.php');
require_once('com/moserv/sweat16/controller/controller.php');


class PasswordResetController extends Controller {

	protected function queryPasswordReset($record) {
		$session = $this->getSession();
		$connection = $session->getConnection();
		$query = $connection->createQuery(
<<<sql
			select
				pr.password_reset_id							as 'password-reset-id',
				(pr.sys_timestamp < date_sub(current_timestamp(), interval 15 minute))	as 'expired',
				(pra.password_reset_apply_id is not null)				as 'applied'
			from sweat16.password_reset pr
				left join sweat16.password_reset_apply pra using (password_reset_id)
			where reset_key = ?
sql
		);

		$query->setString(1, $record['reset-key']);

		$query->open();

		$rows = $query->getResultArray();

		return (count($rows) == 0)? array(0, 0, 0): array(
			$rows[0]['password-reset-id'],
			$rows[0]['expired'],
			$rows[0]['applied']
		);
	}


	protected function newPasswordResetApply($record) {
		$session = $this->getSession();
		$connection = $session->getConnection();
		$query = $connection->createQuery(
<<<sql
			insert into sweat16.password_reset_apply (
				hit_id,
				password_reset_id
			)
			values (?, ?)
sql
		);

		$query->setInt(1, $record['hit-id']);
		$query->setInt(2, $record['password-reset-id']);

		$query->open();

		return $connection->lastId();
	}

	public function execute() {
		$session = $this->getSession();
		$params = $this->getInputParams();

		$record = array();


		list(
			$record['password-reset-id'],
			$record['expired'],
			$record['applied']

		) = $this->queryPasswordReset(array('reset-key' => $params['key']));


		if ($record['password-reset-id'] != 0 && $record['expired'] == 0 && $record['applied'] == 0) {
			$record['password-reset-apply-id'] = $this->newPasswordResetApply(
				array(
					'hit-id'		=> $session->getHitId(),
					'password-reset-id'	=> $record['password-reset-id']
				)
			);
		}

		$this->setOutputParams($record);
	}
}

