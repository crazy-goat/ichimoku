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

class CreateCandles extends Command
{
    use AllPeriods;

    protected static $defaultName = 'forex:create:candles';
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        parent::__construct();
        $this->connection = $connection;
    }

    protected function configure()
    {
        $this->addOption('pair', 'p', InputOption::VALUE_REQUIRED|InputOption::VALUE_IS_ARRAY, 'Forex pair');
        $this->addOption('period', 'P', InputOption::VALUE_REQUIRED, 'Period 1,5,15 ... D,W', Period::H4);
        $this->addOption('date', 'd', InputOption::VALUE_REQUIRED, 'Date start from. Format YYYY-MM-DD');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $pairs = $this->fetchAllPairs();
        $period = Period::fromString($input->getOption('period'));
        $rabbitMQ = RabbitMQWriter::createFromConfig(['exchange' => 'candle.import']);

        foreach ($pairs as $pair) {
            $dateFrom = $this->fetchStartingDate($pair, $period);
            $previousCandle = $this->previousCandle($pair, $period, $dateFrom);

            $interval = new \DateInterval('PT' . PeriodTime::seconds($period) . 'S');
            $dateRange = new \DatePeriod($dateFrom, $interval, new \DateTime('now', new \DateTimeZone('UTC')));

            /** @var \DateTime $time */
            foreach ($dateRange as $time) {
                $output->writeln(sprintf("Pair: %s, time: %s", $pair->symbol(), $time->format('Y-m-d H:i:s')));
                $candle = $this->createFromTicks($pair, $period, $time, $previousCandle);
                if ($candle instanceof Candle) {
                    $rabbitMQ->write($candle);
                    $rabbitMQ->ack();
                    $previousCandle = $candle;
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
            $this->connection->executeQuery('select symbol from tick_data group by symbol order by symbol')->fetchFirstColumn()
        );
    }

    private function fetchStartingDate(Pair $pair, Period $period): \DateTime
    {
        $date = \DateTime::createFromFormat(
            'Y-m-d H:i:s.u',
            $this->connection->executeQuery(
                'SELECT 
                td.time
            FROM
                tick_data td
            WHERE
                td.symbol = :symbol
                    AND td.time >= (SELECT 
                        cd.time
                    FROM
                        candle_data cd
                    WHERE
                        cd.symbol = :symbol
                            AND cd.period = :period
                    ORDER BY cd.time DESC
                    LIMIT 1)
            ORDER BY td.time ASC
            LIMIT 1',
                [
                    'symbol' => $pair->symbol(),
                    'period' => $period->period()
                ],
            )->fetchOne(),
            new \DateTimeZone('UTC')
        );

        if ($date === false) {
            $date = \DateTime::createFromFormat(
                'Y-m-d H:i:s.u',
                $this->connection->executeQuery('SELECT td.time FROM tick_data td ORDER BY td.time ASC LIMIT 1')->fetchOne(),
                new \DateTimeZone('UTC')
            );
        }

        if ($date === false) {
            throw new \Exception('Could not found starting point');
        }

        $unixTime = (int)$date->format('U');
        $date->setTimestamp($unixTime - ($unixTime%PeriodTime::seconds($period)));

        return $date;
    }

    private function createFromTicks(Pair $pair, Period $period, \DateTime $time, ?Candle $last): ?Candle
    {
        $data = $this->connection->executeQuery(
            'SELECT 
                MIN(bid) AS low,
                MAX(bid) AS high,
                (SELECT 
                        otd.bid
                    FROM
                        tick_data AS otd
                    WHERE
                        otd.symbol = :symbol
                            AND otd.time >= :start
                            AND otd.time < :end
                    ORDER BY otd.time ASC
                    LIMIT 1) AS open,
                (SELECT 
                        ctd.bid
                    FROM
                        tick_data ctd
                    WHERE
                        symbol = :symbol
                            AND ctd.time >= :start
                            AND ctd.time < :end
                    ORDER BY ctd.time DESC
                    LIMIT 1) AS close
            FROM
                tick_data
            WHERE
                symbol = :symbol
                    AND `time` >= :start
                    AND `time` < :end',
            [
                'symbol' => $pair->symbol(),
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

    private function previousCandle(Pair $pair, Period $period, \DateTime $dateFrom): ?Candle
    {
        $data = $this->connection->executeQuery(
            'SELECT * FROM candle_data WHERE symbol=:symbol AND period=:period AND time < :dateFrom ORDER BY time DESC LIMIT 1',
            [
                'symbol' => $pair->symbol(),
                'period' => $period->period(),
                'dateFrom' => $dateFrom->format(Candle::DATE_FORMAT)
            ]
        )->fetchAssociative();

        if (is_array($data)) {
            $data['date'] = substr($data['time'], 0, -3);
            return Candle::fromArray($data);
        }

        return null;
    }
}