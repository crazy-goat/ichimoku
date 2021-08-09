<?php

namespace CrazyGoat\Forex\ML\Command;

use CrazyGoat\Forex\Download\Stooq\Command\Download;
use CrazyGoat\Forex\Indicator\CandleWindow;
use CrazyGoat\Forex\Indicator\Highest;
use CrazyGoat\Forex\Indicator\Lowest;
use CrazyGoat\Forex\Indicator\Price;
use CrazyGoat\Forex\Indicator\SMA;
use CrazyGoat\Forex\Math\CandlePrice;
use CrazyGoat\Forex\ML\MultiClassAccuracy;
use CrazyGoat\Forex\Service\AllPeriods;
use CrazyGoat\Forex\Service\StringCounter;
use CrazyGoat\Forex\ValueObject\Candle;
use CrazyGoat\Forex\ValueObject\IndicatorCollection;
use CrazyGoat\Forex\ValueObject\NamedIndicator;
use CrazyGoat\Forex\ValueObject\Pair;
use CrazyGoat\Forex\ValueObject\Period;
use DateTime;
use Doctrine\DBAL\Connection;
use Generator;
use Rubix\ML\Classifiers\MultilayerPerceptron;
use Rubix\ML\Datasets\Labeled;
use Rubix\ML\NeuralNet\ActivationFunctions\ReLU;
use Rubix\ML\NeuralNet\ActivationFunctions\SELU;
use Rubix\ML\NeuralNet\Layers\Activation;
use Rubix\ML\NeuralNet\Layers\Dense;
use Rubix\ML\NeuralNet\Layers\Dropout;
use Rubix\ML\NeuralNet\Optimizers\Adam;
use Rubix\ML\PersistentModel;
use Rubix\ML\Persisters\Filesystem;
use Rubix\ML\Pipeline;
use Rubix\ML\Transformers\L1Normalizer;
use Rubix\ML\Transformers\MinMaxNormalizer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

class LearnDaily extends Command
{
    use AllPeriods;

    private const BUY_STRONG = 'Buy!!!';
    private const SELL_STRONG = 'Sell!!!';
    private const BUY = 'Buy';
    private const SELL = 'Sell';
    private const WAIT = 'Wait';

    protected static $defaultName = 'forex:ml:train:daily';
    private Connection $connection;
    private string $cacheDir;

    public function __construct(Connection $connection, string $cacheDir)
    {
        parent::__construct();
        $this->connection = $connection;
        $this->cacheDir = $cacheDir;
    }

    protected function configure()
    {
        $this->addOption('pair', 'p', InputOption::VALUE_REQUIRED, 'Select from: ' . implode(', ', Download::SYMBOLS));
        $this->addOption('period', 'P', InputOption::VALUE_REQUIRED, 'Period 1,5, ... H1, ... D,W');
        $this->addOption('file', 'f', InputOption::VALUE_REQUIRED, 'Output file');
        $this->addOption('take-profit', 'tr', InputOption::VALUE_REQUIRED, 'Trade Profit in pips', 150);
        $this->addOption('pip-size', 'ps', InputOption::VALUE_REQUIRED, 'Pip size', 0.0001);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->setVerbosity(OutputInterface::VERBOSITY_VERY_VERBOSE);
        $logger = new ConsoleLogger($output);

        $pair = $input->getOption('pair');
        $period = $input->getOption('period') ?? Period::DAILY;
        $pipSize = $input->getOption('pip-size');
        $takeProfit = (float) $input->getOption('take-profit') * $pipSize;

        if ($pair === null) {
            $output->writeln('Pair must be provided. Avail pairs: ' . implode(', ', Download::SYMBOLS));

            return Command::INVALID;
        }

        $pair = Pair::fromString($pair);
        $period = Period::fromString($period);

        $counter = new StringCounter();

        $data = $this->mapData($pair, $period, $takeProfit, $counter);

        $dataset = Labeled::fromIterator($data);

        $trainFile = $pair->first() . $pair->second() . '_' . $period->period() . '_' . $input->getOption('take-profit') . '.rbx';

        if (!file_exists($trainFile) || 1) {
            $estimator = new PersistentModel(
                new Pipeline(
                    [
                    new L1Normalizer()
                    ],
                    new MultilayerPerceptron(
                    [
                        new Dense(200),
                        new Activation(new ReLU()),
                        new Dropout(0.2),
                        new Dense(200),
                        new Activation(new SELU()),
                        new Dropout(0.2),
                        new Dense(100),
                        new Activation(new SELU()),
                        new Dropout(0.2),
                        new Dense(100),
                        new Activation(new SELU()),
                        new Dropout(0.2),
                        new Dense(100),
                    ], 256, new Adam(0.0002)
                    ),
                ),
                new Filesystem($trainFile, false)
            );

            $estimator->setLogger($logger);
            $estimator->train($dataset);
            $estimator->save();
        }

        $output->writeln('Buy!!!: ' . $counter->count(self::BUY_STRONG));
        $output->writeln('Sell!!!: ' . $counter->count(self::SELL_STRONG));
        $output->writeln('Wait: ' . $counter->count(self::WAIT));

        $estimator = PersistentModel::load(new Filesystem($trainFile));

        $logger->info('Making predictions');
        $predictions = $estimator->predict($dataset);

        $metric = new MultiClassAccuracy();
        $scores = $metric->scoreMulti($predictions, $dataset->labels());
        foreach ($scores as $key => $score) {
            $output->writeln($key . " score is: " . $score);
        }

        return Command::SUCCESS;
    }

    /**
     * @param Pair          $pair
     * @param Period        $period
     * @param               $takeProfit
     * @param StringCounter $counter
     *
     * @return Generator
     */
    protected function mapData(
        Pair $pair,
        Period $period,
        $takeProfit,
        StringCounter $counter
    ): \Generator {
        $past = new CandleWindow(4);
        $feature = new CandleWindow(3);

        $indicators = new IndicatorCollection(
            new NamedIndicator('sma50', new SMA(50)),
            new NamedIndicator('sma100', new SMA(50)),
            new NamedIndicator('high9', new Highest(9)),
            new NamedIndicator('low9', new Lowest(9)),
            new NamedIndicator('high13', new Highest(13)),
            new NamedIndicator('low13', new Lowest(13)),
            new NamedIndicator('high26', new Highest(26)),
            new NamedIndicator('low26', new Lowest(26)),
        );

        foreach ($this->readFromDB($pair, $period) as $value) {
            $overflow = $feature->append($value);
            if ($overflow) {
                $overflow = $past->append($value);
                $indicators->append($value);
                if ($overflow && $indicators->ready()) {
                    $action = $this->bestAction($takeProfit, ...$feature->list());
                    $data = [
                        ($indicators->value('high9') - $overflow->close()) * 10000,
                        ($indicators->value('low9') - $overflow->close()) * 10000,
                        ($indicators->value('high13') - $overflow->close()) * 10000,
                        ($indicators->value('low13') - $overflow->close()) * 10000,
                        ($indicators->value('high26') - $overflow->close()) * 10000,
                        ($indicators->value('low26') - $overflow->close()) * 10000,
                        ($indicators->value('sma50') - $overflow->close()) * 10000,
                        $action
                    ];

                    printf(
                        "SMA: %.5f, Highest: %.5f, Lowest: %.5f, sma-close: %.5f\r",
                        ...$data
                    );

                    yield $data;
                    $counter->add($action);
                    //[$min, $max] = [Price::lowest(...$past->list()), Price::highest(...$past->list())];


                    //$draw = new History(40, $min, $max, ...$past->list());
                    //yield [
                    //    ...array_map(
                    //        function ($value) {
                    //            return ((float) ord($value)) / 255.0;
                    //        },
                    //        str_split($draw->draw(false))
                    //    ),
                    //    $action
                    //];
                }
            }
        }
    }

    private function readFromDB(Pair $pair, Period $period): Generator
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
            $row['date'] = DateTime::createFromFormat('Y-m-d H:i:s.u', $row['time'])->format(Candle::DATE_FORMAT);
            yield Candle::fromArray($row);
        }
    }

    private function bestAction(float $takeProfit, Candle ...$candles)
    {
        $startPrice = $candles[0]->open();

        [$min, $max] = [Price::lowest(...$candles), Price::highest(...$candles)];
        $profitSell = $startPrice - $min;
        $profitBuy = $max - $startPrice;

        if ($profitBuy > $profitSell) {
            if ($profitBuy >= $takeProfit) {
                return self::BUY_STRONG;
            }
        } else {
            if ($profitSell >= $takeProfit) {
                return self::SELL_STRONG;
            }
        }

        return self::WAIT;
    }

}