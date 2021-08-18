<?php

namespace CrazyGoat\Forex\Command;

use CrazyGoat\Forex\Indicator\Ichimoku;
use CrazyGoat\Forex\Service\AllPeriods;
use CrazyGoat\Forex\ValueObject\CalculateCandle;
use CrazyGoat\Forex\ValueObject\Candle;
use CrazyGoat\Forex\ValueObject\IchimokuData as IchimokuDataVO;
use CrazyGoat\Forex\ValueObject\MultiPrice;
use CrazyGoat\Forex\ValueObject\Pair;
use CrazyGoat\Forex\ValueObject\Period;
use CrazyGoat\Forex\Writer\MysqlIchimokuWriter;
use CrazyGoat\Forex\Writer\RabbitMQWriter;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class IchimokuData extends Command
{
    use AllPeriods;

    protected static $defaultName = 'forex:ichimoku:data';
    private Connection $connection;
    private MysqlIchimokuWriter $mysqlIchimokuWriter;

    public function __construct(Connection $connection, MysqlIchimokuWriter $mysqlIchimokuWriter)
    {
        parent::__construct();
        $this->connection = $connection;
        $this->mysqlIchimokuWriter = $mysqlIchimokuWriter;
    }

    protected function configure()
    {
        $this->addOption('period', 'P', InputOption::VALUE_REQUIRED, 'Period 1,5,15 ... D,W', Period::H4);
        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Force to rebuild');
        $this->addOption('tenkan', 't', InputOption::VALUE_REQUIRED, 'Force to rebuild', 9);
        $this->addOption('kijun', 'k', InputOption::VALUE_REQUIRED, 'Force to rebuild', 26);
        $this->addOption('spanb', 's', InputOption::VALUE_REQUIRED, 'Force to rebuild', 52);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $pairs = $this->fetchAllPairs();
        $period = Period::fromString($input->getOption('period'));
        $rabbitMQ = RabbitMQWriter::createFromConfig(['exchange' => 'amq.direct']);
        $force = (bool) $input->hasOption('force');

        foreach ($pairs as $pair) {
            $ichimoku = new Ichimoku(
                (int)$input->getOption('tenkan'),
                (int)$input->getOption('kijun'),
                (int)$input->getOption('spanb')
            );
            /** @var CalculateCandle $value */
            foreach ($this->readFromDB($pair, $period) as $value) {
                $candle = $value->candle();
                $tenkan = $kijun = $spanA = $spanB = $chikou = null;
                $ichimoku->append($candle);
                $values = $ichimoku->calculate();
                if (!$value->isCalculated() || $force) {
                    if ($values instanceof MultiPrice) {
                        $tenkan = $values->value('tenkan');
                        $kijun = $values->value('kijun');
                        $spanA = $values->value('spanA');
                        $spanB = $values->value('spanB');
                        $chikou = $values->value('chikou');
                    }

                    $data = new IchimokuDataVO($pair, $candle->time(), $period, $tenkan, $kijun, $spanA, $spanB, $chikou);
                    $rabbitMQ->write($data, 'save_ichimoku_data');
                    $rabbitMQ->ack();
                }
            }
        }
        $output->writeln("");

        return Command::SUCCESS;
    }

    /**
     * @return Pair[]
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    private function fetchAllPairs(): array
    {
        return array_map(
            function (string $symbol): Pair {
                return Pair::fromString($symbol);
            },
            $this->connection->executeQuery('select symbol from candle_data group by symbol order by symbol')->fetchFirstColumn()
        );
    }

    private function readFromDB(Pair $pair, Period $period): \Generator
    {
        $this->connection->getWrappedConnection()->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
        $results = $this->connection->executeQuery(
            'SELECT * FROM candle_data WHERE symbol=:symbol and period=:period order by `time` ASC',
            [
                'symbol' => $pair->symbol(),
                'period' => $period->period()
            ]
        );

        while ($row = $results->fetchAssociative()) {
            $row['date'] = \DateTime::createFromFormat('Y-m-d H:i:s.u', $row['time'])->format(Candle::DATE_FORMAT);
            yield new CalculateCandle(Candle::fromArray($row), (bool) $row['ichimoku']);
        }
    }
}