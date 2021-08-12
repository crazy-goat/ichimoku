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
use Symfony\Component\Console\Helper\ProgressBar;
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
        $this->addOption('period', 'P', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Period 1,5,15 ... D,W');
        $this->addOption('date', 'd', InputOption::VALUE_REQUIRED, 'Date start from. Format YYYY-MM-DD');
        $this->addOption('src-period', 'SP', InputOption::VALUE_REQUIRED, 'Source periods', Period::T);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $sourcePeriod = Period::fromString($input->getOption('src-period'));

        $pair = $input->getOption('pair');
        if ($pair === null) {
            $output->writeln('At least one pair must be provided.');

            return Command::INVALID;
        }

        $pair = Pair::fromString($pair);

        if ($input->getOption('date') === null) {
            $dateFrom = $this->fetchStartingDate($pair, $sourcePeriod);
        } else {
            $dateFrom = \DateTime::createFromFormat('Y-m-d', $input->getOption('date'));
        }

        if (!$dateFrom instanceof \DateTime) {
            $output->writeln('Invalid date');

            return Command::INVALID;
        }

        $periods = array_filter(
            self::getAllPeriods(),
            function (Period $period) use ($sourcePeriod) {
                return !in_array($period->period(), [Period::T, $sourcePeriod->period()], true);
            }
        );

        if ($input->getOption('period') !== []) {
            $periods = array_map(
                function (string $period): Period {
                    return Period::fromString($period);
                },
                $input->getOption('period')
            );
        }

        $dateFrom->setTime(0, 0);
        $rabbitMQ = RabbitMQWriter::createFromConfig(['exchange' => 'candle.import']);

        foreach ($periods as $period) {
            $interval = new \DateInterval('PT' . PeriodTime::seconds($period) . 'S');
            $dateRange = new \DatePeriod($dateFrom, $interval, new \DateTime());

            $progressBar = new ProgressBar($output, iterator_count($dateRange));
            $progressBar->setFormat($period->period() . ': %current%/%max%[%bar%] %percent%%, %remaining%');
            $progressBar->start();

            $lastCandle = null;
            /** @var \DateTime $time */
            foreach ($dateRange as $time) {
                if ($period->period() === Period::T) {
                    $candle = $this->createFromTicks($pair, $period, $time, $lastCandle);
                } else {
                    $candle = $this->createFromCandles($pair, $period, $sourcePeriod, $time, $lastCandle);
                }

                if ($candle instanceof Candle) {
                    $rabbitMQ->write($candle);
                    $rabbitMQ->ack();
                    $progressBar->advance();
                    $lastCandle = $candle;
                }
            }
            $progressBar->finish();
            $output->writeln("");
        }


        return Command::SUCCESS;
    }

    private function fetchStartingDate(Pair $pair, Period $sourcePeriod): \DateTime
    {
        $date = \DateTime::createFromFormat(
            'Y-m-d H:i:s.u',
            $this->connection->executeQuery(
                'SELECT MIN(`time`) FROM ' . ($sourcePeriod->period(
                ) === Period::T ? 'tick_data' : 'candle_data') . ' WHERE symbol = :symbol',
                [
                    'symbol' => $pair->symbol(),
                ],
            )->fetchOne()
        );

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