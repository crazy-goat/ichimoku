<?php

declare(strict_types=1);

namespace CrazyGoat\Forex\Command;

use CrazyGoat\Forex\Reader\RabbitMqReader;
use CrazyGoat\Forex\ValueObject\TickPrice;
use CrazyGoat\Forex\Writer\MysqlTickWriter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ProcessTicks extends Command
{
    protected static $defaultName = 'forex:process:rabbimq:tick';

    protected function configure()
    {
        $this->addOption('queue', null, InputOption::VALUE_REQUIRED, 'Queue name');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $queue = $input->getOption('queue') ?? 'save_mysql_tick';

        $rabbitMq = RabbitMqReader::createFromConfig(['queue' => $queue]);
        $mysql = MysqlTickWriter::createFromConfig([]);

        foreach ($rabbitMq->read() as $msgs) {
            try {
                if ($msgs !== []) {
                    $msg = reset($msgs);
                    foreach ($msgs as $msg) {
                        $data = json_decode($msg->body, true);
                        $mysql->write(TickPrice::fromArray($data));
                    }
                    $mysql->ack();
                    $rabbitMq->ack($msg);
                }
            } catch (\Throwable $exception) {
                var_dump($exception->getMessage());
                if ($msgs !== []) {
                    $last = end($msgs);
                    $mysql->nack($last);
                }
            }
        }
    }
}