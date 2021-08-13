<?php

declare(strict_types=1);

namespace CrazyGoat\Forex\Download\Histdata\Command;

use CrazyGoat\Forex\ValueObject\Pair;
use CrazyGoat\Forex\ValueObject\Period;
use DateInterval;
use DatePeriod;
use FGhazaleh\MultiThreadManager\Exception\InvalidEventArgumentException;
use FGhazaleh\MultiThreadManager\Exception\InvalidListenerArgumentException;
use FGhazaleh\MultiThreadManager\Exception\InvalidThreadException;
use GuzzleHttp\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

//https://candledata.fxcorporate.com/{periodicity}/{instrument}/{year}/{int of week of year}.csv.gz

class Download extends Command
{
    const PAIRS = 'AUDCAD, AUDCHF, AUDJPY, AUDNZD, CADCHF, EURAUD, EURCHF, EURGBP, EURJPY, EURUSD, GBPCHF, GBPJPY,' .
    'GBPNZD, GBPUSD, NZDCAD, NZDCHF, NZDJPY, NZDUSD, USDCAD, USDCHF, USDJPY';
    protected static $defaultName = 'forex:download:histdata:period';
    /**
     * @var Client
     */
    private $client;

    public function __construct(string $name = null)
    {
        parent::__construct($name);
        $this->client = new Client();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $period = Period::fromString($input->getOption('period'));
        $pair = Pair::fromString($input->getOption('pair') ?? '');

        $begin = (new \Datetime())->modify('first day of january')->setTime(0, 0);
        $end = (new \Datetime())->modify('last day of previous month')->setTime(23, 59, 59, 999999);

        $interval = new DateInterval('P1M');
        $dateRange = new DatePeriod($begin, $interval, $end);

        $filesMonth = $this->download($output, $pair, $dateRange, $period);
        $begin = (new \Datetime())->setDate(2001, 1, 1)->setTime(0, 0);
        $end = (new \Datetime())->modify('first day of january')->setTime(0, 0);

        $interval = new DateInterval('P1Y');
        $dateRange = new DatePeriod($begin, $interval, $end);
        $filesYear = $this->download($output, $pair, $dateRange, $period);

        $this->pushToRabbit($output, $pair, $period, ...$filesYear, ...$filesMonth);

        return Command::SUCCESS;
    }

    /**
     * @param OutputInterface $output
     * @param Pair            $pair
     * @param DatePeriod      $dateRange
     * @param Period          $period
     *
     * @return array
     */
    private function download(OutputInterface $output, Pair $pair, DatePeriod $dateRange, Period $period): array
    {
        $files = [];
        $output->writeln('Downloading files');
        $progressBar = new ProgressBar($output, iterator_count($dateRange));
        $progressBar->start();

        foreach (array_reverse(iterator_to_array($dateRange)) as $month) {
            $dir = sprintf("data/histdata.com/%s", $pair->first() . $pair->second());
            $path = sprintf("%s/%s%s-%s.zip", $dir, $month->format('Y'), $month->format('m'), $period->period());

            $process = Process::fromShellCommandline(
                './bin/console forex:download:histdata:month --pair ' . $pair->symbol() . ' --year ' . $month->format('Y')
                . ($dateRange->getDateInterval()->m !== 0 ? (' --month ' . $month->format('m') . ' ') : '')
                . ' --file ' . $path . ' --period ' . $period->period(),
                realpath(__DIR__ . '/../../../../')
            );
            $process->setTimeout(null);
            $process->start();
            $process->wait();
            $progressBar->advance();
            $files[] = $path;
        }
        $progressBar->finish();
        $output->writeln('');

        return $files;
    }

    protected function configure()
    {
        $this->addOption('pair', 'p', InputOption::VALUE_REQUIRED, 'Select from: ' . self::PAIRS);
        $this->addOption('period', 'P', InputOption::VALUE_REQUIRED, 'Select from: T, 1', '1');
    }

    /**
     * @param OutputInterface $output
     * @param Pair            $pair
     * @param Period          $period
     * @param string          ...$files
     */
    private function pushToRabbit(
        OutputInterface $output,
        Pair $pair,
        Period $period,
        string ...$files
    ): void {
        $output->writeln('Pushing files');

        $progressBar = new ProgressBar($output, count($files));
        $progressBar->start();
        foreach ($files as $path) {
            $process = new Process(
                [
                    './bin/console',
                    'forex:download:histdata:process',
                    '--pair',
                    $pair->symbol(),
                    '--file',
                    $path,
                    '--period',
                    $period->period()
                ],
                realpath(__DIR__ . '/../../../../')
            );
            $process->setTimeout(null);
            $process->start();
            $process->wait();
            $progressBar->advance();
        }

        $progressBar->finish();
        $output->writeln('');
    }
}