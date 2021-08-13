<?php

namespace CrazyGoat\Forex\Stream\XTB\Command;

use CrazyGoat\Forex\Stream\XTB\Client\Websocket;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Symbols extends Command
{
    protected static $defaultName = 'forex:stream:xtb:symbols';

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        \Amp\Loop::run(function () use ($output) {
            $client = new Websocket('1869888','NKm?77*!N3Q.aQT');
            foreach (yield $client->symbols() as $symbol => $name) {
                $output->writeln(sprintf('%s: %s', $symbol, $name));
            }
            \Amp\Loop::stop();
        });

        return Command::SUCCESS;
    }
}