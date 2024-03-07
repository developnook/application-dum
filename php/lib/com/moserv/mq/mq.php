<?php

#require_once('com/rabbitmq/autoloader.php');

#use PhpAmqpLib\Connection\AMQPStreamConnection;
#use PhpAmqpLib\Message\AMQPMessage;

abstract class MQ {

	protected $connection;

	protected $host;
	protected $port;
	protected $user;
	protected $pass;
	protected $path;

	public function __construct($host, $port, $user, $pass, $path) {
		$this->host = $host;
		$this->port = $port;
		$this->user = $user;
		$this->pass = $pass;
		$this->path = $path;

		$this->connection = $this->createMQ($host, $port, $user, $pass, $path);
#		$this->connection = AMQPConnection(
#			array(
#				'host'		=> $host,
#				'vhost'		=> $path,
#				'port'		=> $port,
#				'login'		=> $user,
#				'password'	=> $pass
#			)
#		);
	}

	public function getConnection() {
		return $this->connection;
	}

	abstract public function connect();
	abstract public function pconnect();

	abstract protected function createMQ($host, $port, $user, $pass, $path);
	abstract public function createProducer($queueName);
	abstract public function createConsumer($queueName);
}


abstract class Producer {

	protected $mq;
	protected $queueName;

	public function __construct($mq, $queueName) {
		$this->mq = $mq;
		$this->queueName = $queueName;
	}

	abstract public function execute($message);
}

abstract class Consumer {
	protected $mq;
	protected $queueName;

	public function __construct($mq, $queueName) {
		$this->mq = $mq;
		$this->queueName = $queueName;
	}

	abstract public function execute();
}


class RabbitProducer extends Producer {

	protected $channel;
	protected $exchange;
	protected $queue;

	public function __construct($mq, $queueName) {

		parent::__construct($mq, $queueName);

#		$connection = $this->mq->getConnection();
#		$this->channel = $channel = $connection->channel();
#		$channel->queue_declare($this->queueName, false, false, false, false);

		$connection = $this->mq->getConnection();
		$this->channel = $channel = new AMQPChannel($connection);
		$this->exchange = $exchange = new AMQPExchange($channel);
		$this->queue = $queue = new AMQPQueue($channel);

		$queue->setName($queueName);
		$queue->setFlags(AMQP_NOPARAM);
		$queue->declareQueue();
	}

	public function execute($message) {
#		$msg = new AMQPMessage($message);
#		$this->channel->basic_publish($msg, '', $this->queueName);
		$this->exchange->publish($message, $this->queueName);
	}
}

class RabbitMQ extends MQ {

	protected function createMQ($host, $port, $user, $pass, $path) {

#		return new AMQPStreamConnection(
#			$host,
#			$port,
#			$user,
#			$pass,
#			$path,
#			false,
#			'AMQPLAIN',
#			null,
#			'en_US',
#			3.0,
#			120.0,
#			null,
#			true,
#			60
#		);

		return new AMQPConnection(
			array(
				'host'		=> $host,
				'vhost'		=> $path,
				'port'		=> $port,
				'login'		=> $user,
				'password'	=> $pass
			)
		);
	}

	public function createProducer($queueName) {
		return new RabbitProducer($this, $queueName);

	}

	public function createConsumer($queueName) {
		return null;
	}

	public function connect() {
		$this->connection->connect();
	}

	public function pconnect() {
		$this->connection->pconnect();
	}
}
