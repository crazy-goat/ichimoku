<?php

declare(strict_types=1);

namespace CrazyGoat\Forex\Reader;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPTimeoutException;

class RabbitMqReader implements ReaderInterface
{
    private string $queue;
    private AMQPChannel $channel;
    private array $massages = [];

    public function __construct(AMQPStreamConnection $connection, string $queue)
    {
        $this->channel = $connection->channel();
        $this->channel->queue_declare($queue, true);
        //$this->channel->basic_qos(null, 100, null);
        $this->queue = $queue;
    }

    public static function createFromConfig(array $params): RabbitMqReader
    {
        $connection = new AMQPStreamConnection('rabbitmq', 5672, 'guest', 'guest');

        return new RabbitMqReader($connection, $params['queue']);
    }

    public function read(): \Generator
    {
        $this->channel->basic_consume(
            $this->queue,
            __CLASS__,
            false,
            false,
            false,
            false,
            function ($msg) {
                $this->massages[] = $msg;
            }
        );

        while ($this->channel->is_consuming()) {
            if (count($this->massages) >= 50) {
                yield $this->massages;
                $this->massages = [];
            }

            try {
                $this->channel->wait(null, false, 0.1);
            } catch (AMQPTimeoutException $exception) {
                yield $this->massages;
                $this->massages = [];
            }
        }
    }


    public function ack($msg)
    {
        $this->channel->basic_ack($msg->getDeliveryTag(), true);
    }

    public function nack($msg)
    {
        $this->channel->basic_nack($msg->getDeliveryTag(), true);
    }
}