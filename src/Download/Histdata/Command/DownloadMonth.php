<?php

namespace CrazyGoat\Forex\Download\Histdata\Command;

use CrazyGoat\Forex\ValueObject\Pair;
use CrazyGoat\Forex\ValueObject\Period;
use GuzzleHttp\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DownloadMonth extends Command
{
    protected static $defaultName = 'forex:download:histdata:month';

    protected function configure()
    {
        $this->addOption('pair', 'p', InputOption::VALUE_REQUIRED, 'Select from: ' . Download::PAIRS);
        $this->addOption('period', 'P', InputOption::VALUE_REQUIRED, 'Select from: T, M1', 'T');
        $this->addOption('year', 'y', InputOption::VALUE_REQUIRED, 'Date start from. Format YYYY');
        $this->addOption('month', 'm', InputOption::VALUE_REQUIRED, 'Date start from. Format MM');
        $this->addOption('file', 'f', InputOption::VALUE_REQUIRED, 'Output file');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $pair = Pair::fromString($input->getOption('pair') ?? '');
        $file = $input->getOption('file');
        $period = Period::fromString($input->getOption('period'));

        if ($file === null) {
            $output->writeln('File name must be provided');
            return Command::INVALID;
        }

        if (!is_dir(dirname($file))) {
            mkdir(dirname($file), 0777, true);
        }
        if (!file_exists($file)) {
            $client = new Client();
            $htmlUrl = $this->urlForKey($period, $pair, (int)$input->getOption('year'), $input->getOption('month'));

            //die(var_dump($htmlUrl));
            $response = $client->get($htmlUrl);
            preg_match('/value=\"[a-f0-9]{32}\"/m', $response->getBody()->getContents(), $output_array);

            list($key, $value) = explode('=', $output_array[0] ?? '', 2);
            $value = trim($value, '"');

            $client->request(
                'POST',
                'https://www.histdata.com/get.php',
                [
                    'debug' => false,
                    'headers' => [
                        'Referer' => $htmlUrl,
                    ],
                    'sink' => $file,
                    'form_params' => [
                        'tk' => $value,
                        'date' => $input->getOption('year'),
                        'datemonth' => $input->getOption('year').($input->getOption('month') ?? ''),
                        'platform' => 'ASCII',
                        'timeframe' => $period->period() === Period::T ? Period::T : 'M1',
                        'fxpair' => strtolower($pair->first().$pair->second())
                    ]
                ]
            );
        } else {
            $output->writeln('File already exists skipping');
        }
        return Command::SUCCESS;
    }

    public function urlForKey(Period $period, Pair $pair, int $year, int $month = null): string
    {
        $symbol = strtolower($pair->first().$pair->second());

        switch ($period->period()) {
            case Period::T:
                return sprintf(
                    'https://www.histdata.com/download-free-forex-historical-data/?/ascii/tick-data-quotes/%s/%d',
                    $symbol,
                    $year
                ).($month ? '/'.$month : '');
            case Period::M1:
                //return 'https://www.histdata.com/download-free-forex-historical-data/?/ascii/1-minute-bar-quotes/%s/%d';
                return sprintf(
                        'https://www.histdata.com/download-free-forex-historical-data/?/ascii/1-minute-bar-quotes/%s/%d',
                        $symbol,
                        $year
                    ).($month ? '/'.$month : '');
            default:
                throw new \InvalidArgumentException('Period not found');
        }
    }
}