<?php

declare(strict_types=1);

namespace CrazyGoat\Forex\Command;

use CrazyGoat\Forex\Reader\RabbitMqReader;
use CrazyGoat\Forex\Writer\MysqlIchimokuWriter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProcessIchimoku extends Command
{
    protected static $defaultName = 'forex:process:rabbitmq:ichimoku';
    private RabbitMqReader $rabbitmq;
    private MysqlIchimokuWriter $mysql;

    public function __construct(MysqlIchimokuWriter $mysql, RabbitMqReader $rabbitmq)
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
                        $this->mysql->write(\CrazyGoat\Forex\ValueObject\IchimokuData::fromArray($data));
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