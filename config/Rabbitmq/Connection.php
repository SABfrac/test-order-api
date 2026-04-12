<?php
namespace config\Rabbitmq;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Channel\AMQPChannel;


final class Connection
{
    private ?AMQPStreamConnection $conn = null;
    private ?AMQPChannel $ch = null;

    public const EXCHANGE_ORDERS = 'orders';
    public const QUEUE_ORDER_CREATED = 'order_created';
    public const ROUTING_ORDER_CREATED = 'order_created';

    public function __construct(
        private string $host,
        private int    $port,
        private string $user,
        private string $pass,
        private string $vhost = '/',

    )
    {}

    public function channel(): AMQPChannel
    {
        if ($this->ch !== null && (!$this->conn->isConnected() || !$this->ch->is_open())) {
            $this->ch = null;
            $this->conn = null;
        }

        if ($this->ch === null) {
            $this->conn ??= new AMQPStreamConnection($this->host, $this->port, $this->user, $this->pass, $this->vhost);
            $this->ch = $this->conn->channel();

        }

        return $this->ch;
    }

}

