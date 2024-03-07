<?php

require_once('com/moserv/net/url.php');
require_once('com/moserv/sweat16/controller/controller.php');


class PasswordApplyController extends Controller {

	protected function doPasswordApply($record) {
		$session = $this->getSession();
		$connection = $session->getConnection();
		$query = $connection->createQuery(
<<<sql
			update sweat16.password_reset_apply pra
				join sweat16.password_reset pr using (password_reset_id)
				join sweat16.user u on u.user_id = pr.as_user_id
			set
				pra.fr_password = u.password,
				pr.status = 1,
				pr.password_reset_apply_id = pra.password_reset_apply_id,
				u.password = ?
			where pra.password_reset_apply_id = ?
sql
		);

		$query->setString(1, $record['password']);
		$query->setInt(2, $record['password-reset-apply-id']);

		$query->open();

		return $connection->lastId();
	}

	public function execute() {
		$session = $this->getSession();
		$params = $this->getInputParams();

		$record = array();


		$this->doPasswordApply(
			array(
				'password-reset-apply-id'	=> $session->getVar('password-reset-apply-id'),
				'password'			=> $params['password']
			)
		);

		$this->setOutputParams($record);
	}
}

