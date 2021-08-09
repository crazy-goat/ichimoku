<?php
declare(strict_types=1);
namespace CrazyGoat\Forex\Download\Stooq\Command;

use CrazyGoat\Forex\ValueObject\Pair;
use GuzzleHttp\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Download extends Command
{
    public const SYMBOLS = ['USD/CHF'];

    protected static $defaultName = 'forex:download:stooq:daily';
    private string $cacheDir;

    public function __construct(string $cacheDir)
    {
        parent::__construct(null);

        $this->cacheDir = $cacheDir;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $pair = $input->getOption('pair');
        $file = $input->getOption('file');

        if ($pair === null) {
            $output->writeln('Pair must be provided. Avail pairs: ' . implode(', ', Download::SYMBOLS));

            return Command::INVALID;
        }

        $pair = Pair::fromString($pair);

        if ($file === null) {
            $file = $this->cacheDir . '/stooq/' . str_replace('/', '', $pair->symbol() . '_D.csv');
        }

        if (!is_dir(dirname($file))) {
            mkdir(dirname($file), 0777, true);
        }
        $archiveFilename = $this->cacheDir . '/stooq/daily_' . date_format(new \DateTime(), 'Y-m-d') . '.zip';
        if ($this->fetchArchive($archiveFilename)) {
            $content = $this->unpack($archiveFilename, $pair);
            if ($content !== null) {
                file_put_contents($file, $content);
            }
        }
        return Command::SUCCESS;
    }

    private function unpack(string $archive, Pair $pair): ?string {
        $file = 'data/daily/world/currencies\major/'.strtolower($pair->first()).strtolower($pair->second()).'.txt';
        $zip = new \ZipArchive();
        if ($zip->open($archive)) {
            echo "Archive Open".PHP_EOL;
            echo "$file".PHP_EOL;
            $content = $zip->getFromName($file);
            $zip->close();
            return $content;
        }

        return null;
    }

    private function fetchArchive(string $archiveFilename): bool
    {

        var_dump($archiveFilename);
        if (!file_exists($archiveFilename)) {
            $client = new Client();
            $client->request(
                'GET',
                'https://static.stooq.com/db/h/d_world_txt.zip',
                [
                    'debug' => false,
                    'sink' => $archiveFilename,

                ]
            );
        }

        return file_exists($archiveFilename);
    }

    protected function configure()
    {
        $this->addOption('pair', 'p', InputOption::VALUE_REQUIRED, 'Select from: ' . implode(', ', Download::SYMBOLS));
        $this->addOption('file', 'f', InputOption::VALUE_REQUIRED, 'Output file');
    }
}