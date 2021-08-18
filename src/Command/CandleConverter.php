<?php

namespace CrazyGoat\Forex\Command;

use CrazyGoat\Forex\Service\AllPeriods;
use CrazyGoat\Forex\Service\PeriodTime;
use CrazyGoat\Forex\ValueObject\Candle;
use CrazyGoat\Forex\ValueObject\Pair;
use CrazyGoat\Forex\ValueObject\Period;
use CrazyGoat\Forex\Writer\RabbitMQWriter;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CandleConverter extends Command
{
    use AllPeriods;

    protected static $defaultName = 'forex:convert:candles';
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        parent::__construct();
        $this->connection = $connection;
    }

    protected function configure()
    {
        $this->addOption('pair', 'p', InputOption::VALUE_REQUIRED, 'Forex pair');
        $this->addOption('period', 'P', InputOption::VALUE_REQUIRED, 'Period 1,5,15 ... D,W', Period::H4);
        $this->addOption('src-period', 's', InputOption::VALUE_REQUIRED, 'Period 1,5,15 ... D,W', Period::H1);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $pairs = $this->fetchAllPairs();
        $period = Period::fromString($input->getOption('period'));
        $srcPeriod = Period::fromString($input->getOption('src-period'));
        $rabbitMQ = RabbitMQWriter::createFromConfig(['exchange' => 'candle.import']);

        foreach ($pairs as $pair) {
            $lastCandle = null;
            try {
                $dateFrom = $this->fetchStartingDate($pair, $period, $srcPeriod);
            } catch (\Exception $exception) {
                $output->writeln('Error: '.$exception->getMessage());
                continue;
            }
            $interval = new \DateInterval('PT' . PeriodTime::seconds($period) . 'S');
            $dateRange = new \DatePeriod($dateFrom, $interval, new \DateTime('now', new \DateTimeZone('UTC')));
            $output->writeln(sprintf("Pair: %s, time: %s, %s -> %s", $pair->symbol(), $dateFrom->format('Y-m-d H:i:s'), $srcPeriod->period(), $period->period()));
            /** @var \DateTime $time */
            foreach ($dateRange as $time) {

                $candle = $this->createFromCandles($pair, $period, $srcPeriod, $time, $lastCandle);
                if ($candle instanceof Candle) {
                    $rabbitMQ->write($candle);
                    $rabbitMQ->ack();
                    $lastCandle = $candle;
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

    private function fetchStartingDate(Pair $pair, Period $period, Period $srcPeriod): \DateTime
    {
        $date = \DateTime::createFromFormat(
            'Y-m-d H:i:s.u',
            $this->connection->executeQuery(
                'SELECT td.time FROM candle_data td WHERE symbol=:symbol and period=:period ORDER BY td.time ASC LIMIT 1',
                [
                    'period' => $srcPeriod->period(),
                    'symbol' => $pair->symbol()
                ]
            )->fetchOne(),
            new \DateTimeZone('UTC')
        );


        if ($date === false) {
            throw new \Exception(sprintf("Could not found starting point for pair: %s", $pair->symbol()));
        }

        $unixTime = (int) $date->format('U');
        $date->setTimestamp($unixTime - ($unixTime % PeriodTime::seconds($period)));

        return $date;
    }

    private function createFromCandles(Pair $pair, Period $period, Period $srcPeriod, \DateTime $time, ?Candle $last): ?Candle
    {
        $data = $this->connection->executeQuery(
            'SELECT MAX(cd.high) as high, MIN(cd.low) as low, cd.open ,cd.close FROM
                (SELECT
                    open,
                    high,
                    low,
                    close,
                    FIRST_VALUE(open) over (ORDER BY `time`) as `new_open`,
                    LAST_VALUE(close) over (ORDER BY `time`) as `new_close`
                FROM
                    fx_prices.candle_data
                WHERE
                    symbol = :symbol
                        AND period = :period 
                        AND `time` >= :start
                        AND `time` < :end 
                        ORDER BY `time`) as cd',
            [
                'symbol' => $pair->symbol(),
                'period' => $srcPeriod->period(),
                'start' => $time->format('Y-m-d H:i:s.u'),
                'end' => (clone $time)->modify('+' . PeriodTime::seconds($period) . ' seconds')->format('Y-m-d H:i:s.u')
            ]
        )->fetchAssociative();

        if (is_array($data) && $data['high']) {
            return Candle::fromArray(
                array_merge(
                    $data,
                    [
                        'symbol' => $pair->symbol(),
                        'period' => $period->period(),
                        'date' => $time->format(Candle::DATE_FORMAT),
                        'open' => $last ? $last->close() : $data['open']
                    ]
                )
            );
        }

        return null;
    }
}