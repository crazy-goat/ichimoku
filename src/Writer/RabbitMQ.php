<?php
declare(strict_types=1);

namespace CrazyGoat\Forex\Writer;

use CrazyGoat\Forex\ValueObject\TickPrice;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMQ implements WriterInterface
{
    private AMQPChannel $channel;
    private string $exchange;

    public function __construct(AMQPStreamConnection $connection, string $exchange, string $type = AMQPExchangeType::DIRECT)
    {
        $channel = $connection->channel();
        $channel->exchange_declare($exchange, $type, true);
        $this->channel = $channel;
        $this->exchange = $exchange;
    }

    public static function createFromConfig(array $params): RabbitMQ
    {
        $connection = new AMQPStreamConnection('rabbitmq', 5672, 'guest', 'guest');

        return new self($connection, $params['exchange'] ?? 'prices.fanout');
    }

    public function write(\JsonSerializable $price): AMQPMessage
    {
        $data = $price->jsonSerialize();

        $message = new AMQPMessage(
            json_encode($data),
            ['content_type' => 'application/json']
        );
        $this->channel->batch_basic_publish($message, $this->exchange, $data['symbol'] ?? 'unknown');
        return $message;
    }

    public function ack()
    {
        $this->channel->publish_batch();
    }
}