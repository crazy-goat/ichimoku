<?php
declare(strict_types=1);
namespace CrazyGoat\Forex\Download\Stooq\Command;

use CrazyGoat\Forex\ValueObject\Pair;
use GuzzleHttp\Client;
use GuzzleHttp\TransferStats;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class DownloadHourly extends Command
{
    public const SYMBOLS = ['USD/CHF'];

    protected static $defaultName = 'forex:download:stooq:hourly';
    private string $cacheDir;

    public function __construct(string $cacheDir)
    {
        parent::__construct();
        $this->cacheDir = $cacheDir;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $pairs = $input->getOption('pair');

        if ($pairs === []) {
            $output->writeln('Pair must be provided. Avail pairs: ' . implode(', ', Download::SYMBOLS));

            return Command::INVALID;
        }
        $progressBar = new ProgressBar($output, 0);
        $archiveFilename = $this->cacheDir . '/stooq/hourly_' . date_format(new \DateTime(), 'Y-m-d') . '.zip';

        if (!is_dir(dirname($archiveFilename))) {
            mkdir(dirname($archiveFilename), 0777, true);
        }

        if ($this->fetchArchive($archiveFilename, $progressBar)) {
            foreach ($pairs as $pair) {
                $pair = Pair::fromString($pair);
                $file = $this->cacheDir . '/stooq/' . str_replace('/', '', $pair->symbol() . '_H.csv');

                if (!is_dir(dirname($file))) {
                    mkdir(dirname($file), 0777, true);
                }

                $content = $this->unpack($archiveFilename, $pair);
                if ($content !== null) {
                    $output->writeln('Unpack date for pair: '.$pair->symbol());
                    file_put_contents($file, $content);
                    $process = new Process(
                        [
                            './bin/console',
                            'forex:download:stooq:process:hourly',
                            '--pair',
                            $pair->symbol()
                        ],
                        realpath(__DIR__ . '/../../../../')
                    );
                    $process->setTimeout(null);
                    $process->start();
                    $process->wait();

                } else {
                    $output->writeln('No data for pair: '.$pair->symbol());
                }
            }
        }
        return Command::SUCCESS;
    }

    private function unpack(string $archive, Pair $pair): ?string {
        $files = [
            'data/hourly/world/currencies\major/'.strtolower($pair->first()).strtolower($pair->second()).'.txt',
            'data/hourly/world/currencies\other/'.strtolower($pair->first()).strtolower($pair->second()).'.txt',
            ];
        $zip = new \ZipArchive();
        if ($zip->open($archive)) {
            foreach ($files as $file) {
                $content = $zip->getFromName($file);
                if ($content !== false) {
                    $zip->close();
                    return $content;
                }
            }
            $zip->close();
        }

        return null;
    }

    private function fetchArchive(string $archiveFilename, ProgressBar $progressBar): bool
    {
        if (!file_exists($archiveFilename)) {
            $client = new Client();
            $client->request('GET', 'https://stooq.com/q/l/s/?t='.substr(uniqid(),0,4));
            $client->request(
                'GET',
                'https://static.stooq.pl/db/h/h_world_txt.zip',
                [
                    'debug' => true,
                    'sink' => $archiveFilename,
                ]
            );
        }

        return file_exists($archiveFilename);
    }

    protected function configure()
    {
        $this->addOption('pair', 'p', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Select from: ' . implode(', ', Download::SYMBOLS));
        $this->addOption('file', 'f', InputOption::VALUE_REQUIRED, 'Output file');
    }
}