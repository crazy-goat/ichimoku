<?php

namespace CrazyGoat\Forex\Draw\Command;

use CrazyGoat\Forex\Download\Stooq\Command\Download;
use CrazyGoat\Forex\DTO\Offset;
use CrazyGoat\Forex\DTO\PriceArray;
use CrazyGoat\Forex\Indicator\CandleWindow;
use CrazyGoat\Forex\Indicator\Ichimoku;
use CrazyGoat\Forex\Indicator\Price;
use CrazyGoat\Forex\Indicator\SMA;
use CrazyGoat\Forex\ValueObject\Candle;
use CrazyGoat\Forex\ValueObject\IndicatorCollection;
use CrazyGoat\Forex\ValueObject\NamedIndicator;
use CrazyGoat\Forex\ValueObject\Pair;
use CrazyGoat\Forex\ValueObject\Period;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Terminal;

class History extends Command
{
    protected static $defaultName = 'forex:draw:history';

    public function __construct(Connection $connection)
    {
        parent::__construct();
        $this->connection = $connection;
    }

    protected function configure()
    {
        $this->addOption('pair', 'p', InputOption::VALUE_REQUIRED, 'Pair');
        $this->addOption('period', 'P', InputOption::VALUE_REQUIRED, 'Period');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $pair = $input->getOption('pair');

        if ($pair === null) {
            $output->writeln('Pair must be provided. Avail pairs: ' . implode(', ', Download::SYMBOLS));

            return Command::INVALID;
        }

        $period = Period::fromString($input->getOption('period') ?? Period::M1);

        $terminal = new Terminal();

        $past = new CandleWindow($terminal->getWidth());

        $pair = Pair::fromString($pair);

        $indicators = new IndicatorCollection(
            $terminal->getWidth(),
            new NamedIndicator('ichimoku', new Ichimoku()),
        );

        foreach ($this->readFromDB($pair, $period) as $value) {
            $indicators->append($value);
            $overflow = $past->append($value);
            if ($overflow && $indicators->ready()) {
                [$min, $max] = [Price::lowest(...$past->list()), Price::highest(...$past->list())];

                $draw = new \CrazyGoat\Forex\Draw\History($terminal->getHeight(), $min, $max, ...$past->list());

                $tenkan = PriceArray::fromNamedMultiPrice('tenkan', ...$indicators->history('ichimoku'));
                $kijun = PriceArray::fromNamedMultiPrice('kijun', ...$indicators->history('ichimoku'));
                $chikou = PriceArray::fromNamedMultiPrice('chikou', ...$indicators->history('ichimoku'));
                $chikou2 = Offset::move(-26, ...$chikou);

                $output->write("\033[0;0f");
                $output->write($draw->drawValues(false, ...$chikou2));
                usleep(100000);
            }
        }
        return Command::SUCCESS;
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
            yield Candle::fromArray($row);
        }
    }
}