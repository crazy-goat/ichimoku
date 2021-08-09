<?php
declare(strict_types=1);
namespace CrazyGoat\Forex\WebSocket;

use Amp\Loop;
use CrazyGoat\Forex\ValueObject\PairCollection;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exchange\AMQPExchangeType;

class WebSocketClient
{
    private string $loopId = '';
    private AMQPStreamConnection $connection;
    private AMQPChannel $channel;
    private string $queue;

    public function __construct(AMQPStreamConnection $connection, int $clientId, PairCollection $pairCollection)
    {
        $this->queue = uniqid('client_') . uniqid('-');
        $exchange = 'prices.websocket';
        $this->connection = $connection;
        $this->channel = $this->connection->channel();
        $this->channel->queue_declare($this->queue, false, false, true);
        $this->channel->exchange_declare($exchange, AMQPExchangeType::DIRECT, true);

        foreach ($pairCollection->list() as $pair) {
            $this->channel->queue_bind($this->queue, $exchange, $pair->symbol());
        }

        $this->loopId = Loop::repeat(10, function () {
            $this->channel->wait(null, true);
        });
    }

    public function consume(int $clientId, \Closure $callable)
    {
        $this->channel->basic_consume($this->queue, $clientId, false, false, false, false, $callable);
    }

    public function close()
    {
        Loop::cancel($this->loopId);
        $this->channel->close();
        //$this->connection->close();
    }
}