<?php

use consumer\OrderConsumer;
use  config\Rabbitmq\Connection;


require_once __DIR__ . '/vendor/autoload.php';

$rabbitConnection = new Connection('rabbitmq', 5672, 'guest', 'guest');

$consumer = new OrderConsumer
(
                  $rabbitConnection,
       Connection::QUEUE_ORDER_CREATED,
    Connection::EXCHANGE_ORDERS
);

try {
    $consumer->consume();
} catch (\Throwable $e) {
    file_put_contents('php://stderr', "[CRITICAL] Worker stopped: {$e->getMessage()}\n");
    exit(1);
}
