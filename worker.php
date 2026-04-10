<?php

use consumer\OrderConsumer;
use  config\Rabbitmq\Connection;



require_once __DIR__ . '/vendor/autoload.php';

// 1. Инициализация инфраструктуры (аналогично index.php)
$rabbitConnection = new Connection('rabbitmq', 5672, 'guest', 'guest');


$consumer = new OrderConsumer(
    $rabbitConnection,
    Connection::QUEUE_ORDER_CREATED,
    Connection::EXCHANGE_ORDERS
);

echo " [*] Ожидание сообщений в " . Connection::QUEUE_ORDER_CREATED . ". Нажмите CTRL+C для выхода\n";

try {
    // 3. Запуск бесконечного цикла прослушивания
    $consumer->consume();
} catch (\Throwable $e) {
    file_put_contents('php://stderr', "[CRITICAL] Worker stopped: {$e->getMessage()}\n");
    exit(1);
}
