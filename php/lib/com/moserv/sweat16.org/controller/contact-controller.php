<?php

require_once('com/moserv/sweat16/controller/controller.php');


class ContactController extends Controller {
	
	protected function newContact($record) {
		$session = $this->getSession();
                $connection = $session->getConnection();

                $query = $connection->createQuery(
<<<sql
                        insert into sweat16.contact (
				user_id,
				purchase_order_id,
				title,
				message
                        )
                        values (?, ?, ?, ?)
sql
                );

                $query->setInt(1, $record['user-id']);
                $query->setInt(2, $record['purchase-order-id']);
                $query->setString(3, $record['title']);
                $query->setString(4, $record['message']);

                $query->open();

                return $connection->lastId();	
	}

	protected function queryPurchaseOrder($record) {
		$session = $this->getSession();
		$connection = $session->getConnection();

		$query = $connection->createQuery(
<<<sql
			select
				po_code as 'po-code'
			from sweat16.purchase_order
			where purchase_order_id = ?
sql
		);

		$query->setInt(1, $record['purchase-order-id']);

		$query->open();
		$rows = $query->getResultArray();

		return (count($rows) == 0)? array(): array($rows[0]['po-code']);
	}


	public function execute() {
		global $_SESSION;

		$session = $this->getSession();
		$params = $this->getInputParams();
		
		$record = array();
		$record['contact-id'] = $this->newContact(
			array(
				'user-id'		=> $session->getVar('user-id'),
				'purchase-order-id'	=> $params['purchase-order-id'],
				'title'			=> $params['title'],
				'message'		=> $params['message']
			)
		);

		list($record['po-code']) = $this->queryPurchaseOrder(array('purchase-order-id' => $params['purchase-order-id']));

		$this->setOutputParams($record);
	}
}

