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
    private MysqlCandleWriter $mysqlWriter;

    public function __construct(MysqlCandleWriter $mysqlWriter)
    {
        parent::__construct();
        $this->mysqlWriter = $mysqlWriter;
    }

    protected function configure()
    {
        $this->addOption('queue', null, InputOption::VALUE_REQUIRED, 'Queue name');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $queue = $input->getOption('queue') ?? 'save_mysql_candle';

        $rabbitMq = RabbitMqReader::createFromConfig(['queue' => $queue]);

        foreach ($rabbitMq->read() as $massages) {
            try {
                if ($massages !== []) {
                    $massage = reset($massages);
                    foreach ($massages as $massage) {
                        $data = json_decode($massage->body, true);
                        $this->mysqlWriter->write(Candle::fromArray($data));
                    }
                    $this->mysqlWriter->ack();
                    $rabbitMq->ack($massage);
                }
            } catch (\Throwable $exception) {
                if ($massages !== []) {
                    $this->mysqlWriter->nack();
                }
            }
        }
    }
}