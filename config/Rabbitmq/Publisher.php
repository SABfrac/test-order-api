<?php
namespace config\Rabbitmq;


use PhpAmqpLib\Message\AMQPMessage;


class Publisher
{

    public function __construct(
                                private Connection $rabbit,
                                private string $exchange,
                                private string $routingKey  )
    {}

    public function publishOrderCreated(array $payload): void
    {
        $ch = $this->rabbit->channel();


        $msg = new AMQPMessage(
            json_encode($payload, JSON_UNESCAPED_UNICODE),
            [
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT
            ]
        );

        $ch->basic_publish($msg, $this->exchange, $this->routingKey);
    }
}
