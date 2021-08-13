<?php
declare(strict_types=1);

namespace CrazyGoat\Forex\Stream\XTB\Command;

use CrazyGoat\Forex\Stream\XTB\Client\Websocket;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

class Symbols extends Command
{
    protected static $defaultName = 'forex:stream:xtb:symbols';

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        \Amp\Loop::run(function () use ($output) {
            $client = new Websocket('1869888','NKm?77*!N3Q.aQT');
            $client->setLogger(new ConsoleLogger($output));
            foreach (yield $client->symbols() as $symbol => $name) {
                $output->write(sprintf('--pair %s ', $symbol));
            }
            \Amp\Loop::stop();
        });

        return Command::SUCCESS;
    }
}