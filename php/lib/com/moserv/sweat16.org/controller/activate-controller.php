<?php

require_once('com/moserv/sweat16/controller/controller.php');


class ActivateController extends Controller {

	protected function updateUserStatus($record) {
		$session = $this->getSession();
		$connection = $session->getConnection();
		$query = $connection->createQuery(
<<<sql
			update sweat16.user
				set enabled = 1
			where activate_key = ?
			and enabled = -1
sql
		);

		$query->setString(1, $record['key']);
		$query->open();

		return $query->getAffectedRows();
	}


	public function execute() {
		$params = $this->getInputParams();
		$record = array();

		$record['count'] = $this->updateUserStatus(array('key' => $params['key']));

		$this->setOutputParams($record);
	}
}

