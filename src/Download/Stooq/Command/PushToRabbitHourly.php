<?php
declare(strict_types=1);
namespace CrazyGoat\Forex\Download\Stooq\Command;

use CrazyGoat\Forex\ValueObject\Candle;
use CrazyGoat\Forex\ValueObject\Pair;
use CrazyGoat\Forex\ValueObject\Period;
use CrazyGoat\Forex\ValueObject\TickPrice;
use CrazyGoat\Forex\Writer\RabbitMQWriter;
use GuzzleHttp\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PushToRabbitHourly extends Command
{
    protected static $defaultName = 'forex:download:stooq:process:hourly';
    private string $cacheDir;

    public function __construct(string $cacheDir)
    {
        parent::__construct();

        $this->cacheDir = $cacheDir;
    }

    protected function configure()
    {
        $this->addOption('pair', 'p', InputOption::VALUE_REQUIRED, 'Select from: ' . \CrazyGoat\Forex\Download\Histdata\Command\Download::PAIRS);
        $this->addOption('date', 'd', InputOption::VALUE_REQUIRED, 'Date start from. Format YYYY-MM');
        $this->addOption('file', 'f', InputOption::VALUE_REQUIRED, 'Output file');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        ini_set('memory_limit', '-1');

        $pair = $input->getOption('pair');
        $file = $input->getOption('file');

        if ($pair === null) {
            $output->writeln('Pair must be provided. Avail pairs: ');
            return Command::INVALID;
        }

        $pair = Pair::fromString($pair);

        if ($file === null) {
            $file = $this->cacheDir . '/stooq/' . str_replace('/', '', $pair->symbol() . '_H.csv');
        }

        $rabbitMQ = RabbitMQWriter::createFromConfig(['exchange' => 'candle.import']);

        $content = file_get_contents($file);
        $prices = preg_split('/\r\n|\r|\n/', $content);
        $header = array_shift($prices);
        if ($header === '<TICKER>,<PER>,<DATE>,<TIME>,<OPEN>,<HIGH>,<LOW>,<CLOSE>,<VOL>,<OPENINT>') {
            foreach ($prices as $key => $price) {
                if (strlen($price)) {
                    $data = explode(',', $price);
                    $date = \DateTime::createFromFormat('YmdHis', $data[2].$data[3]);
                    $date->modify('-59 minutes');
                    $date->setTime((int)$date->format('H'), 0);
                    $candle = new Candle(
                        $pair,
                        Period::fromString($data[1] == 60 ? 'H1': ''),
                        $date,
                        (float) $data[4],
                        (float) $data[5],
                        (float) $data[6],
                        (float) $data[7]
                    );
                    $rabbitMQ->write($candle);

                    if ($key%100 === 0) {
                        $rabbitMQ->ack();
                    }
                }
            }
            $rabbitMQ->ack();
        }

        return Command::SUCCESS;
    }
}