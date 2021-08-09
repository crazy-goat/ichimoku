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

    protected function configure()
    {
        $this->addOption('queue', null, InputOption::VALUE_REQUIRED, 'Queue name');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $queue = $input->getOption('queue') ?? 'save_mysql_candle';

        $rabbitMq = RabbitMqReader::createFromConfig(['queue' => $queue]);
        $mysql = MysqlCandleWriter::createFromConfig([]);

        foreach ($rabbitMq->read() as $msgs) {
            try {
                if ($msgs !== []) {
                    $msg = reset($msgs);
                    foreach ($msgs as $msg) {
                        $data = json_decode($msg->body, true);
                        $mysql->write(Candle::fromArray($data));
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