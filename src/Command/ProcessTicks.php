<?php

declare(strict_types=1);

namespace CrazyGoat\Forex\Command;

use CrazyGoat\Forex\Reader\RabbitMqReader;
use CrazyGoat\Forex\ValueObject\TickPrice;
use CrazyGoat\Forex\Writer\MysqlTickWriter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProcessTicks extends Command
{
    protected static $defaultName = 'forex:process:rabbimq:tick';
    private RabbitMqReader $rabbitmq;
    private MysqlTickWriter $mysql;

    public function __construct(MysqlTickWriter $mysql, RabbitMqReader $rabbitmq)
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
                        $this->mysql->write(TickPrice::fromArray($data));
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