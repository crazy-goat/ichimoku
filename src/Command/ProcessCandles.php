<?php

declare(strict_types=1);

namespace CrazyGoat\Forex\Command;

use CrazyGoat\Forex\Reader\RabbitMqReader;
use CrazyGoat\Forex\ValueObject\Candle;
use CrazyGoat\Forex\Writer\MysqlCandleWriter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ProcessCandles extends Command
{
    protected static $defaultName = 'forex:process:rabbimq:candle';
    private MysqlCandleWriter $mysql;
    private RabbitMqReader $rabbitmq;

    public function __construct(MysqlCandleWriter $mysql, RabbitMqReader $rabbitmq)
    {
        parent::__construct();
        $this->mysql = $mysql;
        $this->rabbitmq = $rabbitmq;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        foreach ($this->rabbitmq->read() as $massages) {
            try {
                if ($massages !== []) {
                    $massage = reset($massages);
                    foreach ($massages as $massage) {
                        $data = json_decode($massage->body, true);
                        $this->mysql->write(Candle::fromArray($data));
                    }
                    $this->mysql->ack();
                    $this->rabbitmq->ack($massage);
                }
            } catch (\Throwable $exception) {
                if ($massages !== []) {
                    $massage = reset($massages);
                    $this->mysql->nack();
                    $this->rabbitmq->nack($massage);
                }
            }
        }
    }
}