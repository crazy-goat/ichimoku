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
        $this->addOption('pair', 'p', InputOption::VALUE_REQUIRED, 'Forex pair');
        $this->addOption('period', 'P', InputOption::VALUE_REQUIRED, 'Period 1,5,15 ... D,W', 'H4');
        $this->addOption('date', 'd', InputOption::VALUE_REQUIRED, 'Date start from. Format YYYY-MM-DD');

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $pairs = $this->fetchAllPairs();
        $period = Period::fromString($input->getOption('period'));
        $rabbitMQ = RabbitMQWriter::createFromConfig(['exchange' => 'candle.import']);


        foreach ($pairs as $pair) {
            $lastCandle = null;
            $dateFrom = $this->fetchStartingDate($pair, new Period(Period::H4));
            $dateFrom->setTime(0, 0);
            $interval = new \DateInterval('PT' . PeriodTime::seconds($period) . 'S');
            $dateRange = new \DatePeriod($dateFrom, $interval, new \DateTime());

            /** @var \DateTime $time */
            foreach ($dateRange as $time) {
                $output->writeln(sprintf("Pair: %s, time: %s", $pair->symbol(), $time->format('Y-m-d H:i:s')));
                $candle = $this->createFromTicks($pair, $period, $time, $lastCandle);
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
                    AND td.time > (SELECT 
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
            )->fetchOne()
        );

        if ($date === false) {
            $date = \DateTime::createFromFormat(
                'Y-m-d H:i:s.u',
                $this->connection->executeQuery('SELECT td.time FROM tick_data td ORDER BY td.time ASC LIMIT 1')->fetchOne()
            );

        }

        if ($date === false) {
            throw new \Exception('Could not found starting point');
        }

        return $date;
    }

    private function createFromTicks(Pair $pair, Period $period, \DateTime $time, ?Candle $last): ?Candle
    {
        $data = $this->connection->executeQuery(
            'SELECT MAX(td.bid) as high, MIN(td.bid) as low, td.open , td.close FROM
                (SELECT 
                    bid,
                    FIRST_VALUE(bid) over (ORDER BY `time`) as `open`,
                    LAST_VALUE(bid) over (ORDER BY `time`) as `close`
                FROM
                    fx_prices.tick_data
                WHERE
                    symbol = :symbol
                        AND `time` >= :start
                        AND `time` < :end 
                        ORDER BY `time`) as td',
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
}