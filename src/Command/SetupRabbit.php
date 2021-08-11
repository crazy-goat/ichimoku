<?php
declare(strict_types=1);
namespace CrazyGoat\Forex\Command;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Wire\AMQPTable;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SetupRabbit extends Command
{
    protected static $defaultName = 'setup:rabbitmq';

    private AMQPStreamConnection $rabbitMQ;

    public function __construct(AMQPStreamConnection $rabbitMQ)
    {
        parent::__construct();
        $this->rabbitMQ = $rabbitMQ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->createExchange();
        $this->createQueue();
        return Command::SUCCESS;
    }

    public function createExchange(): void
    {
        $channel = $this->rabbitMQ->channel();
        $channel->exchange_declare('candle.import', AMQPExchangeType::FANOUT, false, true, false );
        $channel->exchange_declare('prices.fanout', AMQPExchangeType::FANOUT, false, true, false );
        $channel->exchange_declare('prices.import', AMQPExchangeType::FANOUT, false, true, false );
        $channel->exchange_declare('prices.websocket', AMQPExchangeType::DIRECT, false, true, false );

        $channel->exchange_bind('prices.import', 'prices.fanout');
        $channel->exchange_bind('prices.websocket', 'prices.fanout');
        $channel->close();
    }

    private function createQueue()
    {
        $args = new AMQPTable();
        $args->set('x-queue-mode', 'lazy');
        $channel = $this->rabbitMQ->channel();
        $channel->queue_declare('save_mysql_tick', false, true, false, false, false, $args);
        $channel->queue_declare('save_mysql_candle', false, true, false, false, false, $args);
        $channel->close();
    }
}