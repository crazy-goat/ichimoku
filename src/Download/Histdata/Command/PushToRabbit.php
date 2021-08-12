<?php

namespace CrazyGoat\Forex\Download\Histdata\Command;

use CrazyGoat\Forex\Download\Histdata\DTO\HistdataPeriod;
use CrazyGoat\Forex\ValueObject\Candle;
use CrazyGoat\Forex\ValueObject\Pair;
use CrazyGoat\Forex\ValueObject\Period;
use CrazyGoat\Forex\ValueObject\TickPrice;
use CrazyGoat\Forex\Writer\RabbitMQWriter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PushToRabbit extends Command
{
    protected static $defaultName = 'forex:download:histdata:process';

    protected function configure()
    {
        $this->addOption('pair', 'p', InputOption::VALUE_REQUIRED, 'Select from: ' . Download::PAIRS);
        $this->addOption('date', 'd', InputOption::VALUE_REQUIRED, 'Date start from. Format YYYY-MM');
        $this->addOption('file', 'f', InputOption::VALUE_REQUIRED, 'Output file');
        $this->addOption('period', 'P', InputOption::VALUE_REQUIRED, 'Select from: T, M1', 'T');
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

        $rabbitMQ = RabbitMQWriter::createFromConfig(
            ['exchange' => $period->period() === Period::T ? 'prices.import' : 'candle.import']
        );


        $zip = new \ZipArchive();
        if ($zip->open($file)) {

            $content = $zip->getFromName($this->getDataFilename($zip));
            $prices = preg_split('/\r\n|\r|\n/', $content);
            foreach ($prices as $key => $price) {

                if ($period->period() === Period::T) {
                    $this->importTick($pair, $price, $rabbitMQ);
                } else {
                    $this->importCandle($pair, $price, $rabbitMQ);
                }

                if ($key%100 === 0) {
                    $rabbitMQ->ack();
                }
            }
            $rabbitMQ->ack();
            $zip->close();
        } else {
            //$output->writeln($zip->)
        }
        return Command::SUCCESS;
    }

    /**
     * @throws \Exception
     */
    private function getDataFilename(\ZipArchive $zipArchive): string
    {
        foreach (range(0, $zipArchive->numFiles) as $index) {
            $filename = $zipArchive->getNameIndex($index);
            if ($filename === false) {
                continue;
            }

            if (strpos($filename, '.csv') !== false) {
                return $filename;
            }
        }

        throw new \Exception('Data file not found.');
    }

    private function importCandle(Pair $pair, string $price, $rabbitMQ){
        $items = explode(';', $price,6);
        if (count($items) < 6) {
            return;
        }

        $date = \DateTime::createFromFormat('Ymd His', $items[0]);
        $open = (float) $items[1];
        $high = (float) $items[2];
        $low = (float) $items[3];
        $close = (float) $items[4];
        try {
            $rabbitMQ->write(
                new Candle(
                    $pair,
                    new Period(Period::M1),
                    $date,
                    $open,
                    $high,
                    $low,
                    $close
                )
            );
        } catch (\Throwable $exception) {
            echo "Exception: ".$exception->getMessage();
        }
    }

    private function importTick(Pair $pair, string $price, $rabbitMQ){
        $items = explode(',', $price,3);

        if (count($items) < 3) {
            return;
        }

        $date = \DateTime::createFromFormat('Ymd Hisv', $items[0]);
        $bid = (float) $items[1];
        $ask = (float) $items[2];
        try {
            $rabbitMQ->write(new TickPrice($pair, $date, $bid, $ask));
        } catch (\Throwable $exception) {
            echo "Exception: ".$exception->getMessage();
        }
    }
}