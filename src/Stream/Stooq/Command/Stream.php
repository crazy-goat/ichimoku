<?php

declare(strict_types=1);

namespace CrazyGoat\Forex\Stream\Stooq\Command;

use CrazyGoat\Forex\Stream\Stooq\DTO\StooqSymbols;
use CrazyGoat\Forex\Stream\Stooq\DTO\StooqTickPriceCollection;
use CrazyGoat\Forex\ValueObject\Pair;
use CrazyGoat\Forex\ValueObject\PairCollection;
use CrazyGoat\Forex\Writer\RabbitMQWriter;
use GuzzleHttp\Client;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Stream extends Command
{
    private RabbitMQWriter $rabbitMQ;

    public function __construct()
    {
        parent::__construct();
        $this->rabbitMQ = RabbitMQWriter::createFromConfig([]);
    }

    protected function configure()
    {
        $this->addOption('pair', 'p', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Queue name');
    }

    protected static $defaultName = 'forex:stream:stooq';

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $pairs = $input->getOption('pair');

        if ($pairs === []) {
            $output->writeln('You must provide pair');

            return Command::INVALID;
        }

        $body = $this->connectStooq($pairs)->getBody();

        $row = '';
        while (!$body->eof()) {
            $char = $body->read(1);
            if ($char !== "\n") {
                $row .= $char;
            } else {
                $this->pushToRabbit($row);
                $row = '';
            }
        }

        return Command::SUCCESS;
    }

    private function pushToRabbit(string $row): void
    {
        try {
            $prices = StooqTickPriceCollection::fromString($row);
            $last = null;
            foreach ($prices->prices() as $tick) {
                $last = $this->rabbitMQ->write($tick);
            }
            if ($last instanceof AMQPMessage) {
                $this->rabbitMQ->ack();
            }
        } catch (\Exception $e) {
            echo 'Failed to parse row: ' . $row;
        }
    }

    private function connectStooq($pairs): ResponseInterface
    {
        $symbols = StooqSymbols::toStooqSymbols(
            new PairCollection(
                ...
                array_map(
                    function (string $symbol): Pair {
                        return Pair::fromString($symbol);
                    },
                    $pairs
                )
            )
        );

        $client = new Client();

        return $client->request(
            'POST',
            'https://aq.stooq.pl/?q=' . implode('+', $symbols),
            [
                'stream' => true,
                'headers' => [
                    'Referer' => 'https://stooq.pl/',
                    'Accept' => 'text/event-stream',
                    'Connection' => 'keep-alive',
                    'Origin' => 'https://stooq.pl'
                ]
            ]
        );
}
}