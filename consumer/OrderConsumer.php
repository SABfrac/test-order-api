<?php

namespace consumer;

use config\Rabbitmq\Connection;
use PhpAmqpLib\Message\AMQPMessage;



class OrderConsumer
{
    public function __construct(
        private Connection $c,
        private string     $queueName,
        private string     $exchangeName)
    {}

    public function consume(): void
    {
        $ch = $this->c->channel();

        $ch->exchange_declare($this->exchangeName, 'direct', false, true, false);
        $ch->queue_declare($this->queueName, false, true, false, false);
        $ch->queue_bind($this->queueName, $this->exchangeName, $this->queueName);

        $ch->basic_qos(null, 10, null);
        $logDir = __DIR__ . '/../logs';


        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }
        $logFile = $logDir . '/orders.log';

        $ch->basic_consume(
            $this->queueName, '', false, false, false, false,
            function (AMQPMessage $m) use ($logFile): void {
                try {

                    $body = (string) $m->getBody();
                    $logEntry = "[" . date('Y-m-d H:i:s') . "] Order Created: " . $body . PHP_EOL;
                    file_put_contents($logFile . '/../logs/orders.log', $logEntry, FILE_APPEND);

                    $m->ack();
                } catch (\Throwable $e) {
                    file_put_contents('php://stderr', "[✗] {$e->getMessage()}\n");
                    $m->nack(false, false);
                }
            }
        );
        while ($ch->is_consuming()) $ch->wait();
    }
}

