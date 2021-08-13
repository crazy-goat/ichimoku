<?php

namespace CrazyGoat\Forex\Stream\XTB\Command;

use CrazyGoat\Forex\Stream\XTB\Client\Websocket;
use CrazyGoat\Forex\Stream\XTB\DTO\XTBTickPrice;
use CrazyGoat\Forex\ValueObject\Pair;
use CrazyGoat\Forex\ValueObject\TickPrice;
use CrazyGoat\Forex\Writer\RabbitMQWriter;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

class Stream extends Command
{
    protected static $defaultName = 'forex:stream:xtb';
    private RabbitMQWriter $rabbitMQ;

    public function __construct(RabbitMQWriter $rabbitMQ)
    {
        parent::__construct();
        $this->rabbitMQ = $rabbitMQ;
    }

    protected function configure()
    {
        $this->addOption('pair', 'p', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Pair to listen');
        $this->addOption('password', null, InputOption::VALUE_REQUIRED, 'Password');
        $this->addOption('username', null, InputOption::VALUE_REQUIRED, 'User id');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        \Amp\Loop::run(function () use ($input, $output) {
            //if ($input->getOption('password') === null || $input->getOption('username') === null ) {
            //    throw new \InvalidArgumentException('Username and Password must be provided');
            //}

            $verbosityLevelMap = [
                LogLevel::NOTICE => OutputInterface::VERBOSITY_NORMAL,
                LogLevel::INFO => OutputInterface::VERBOSITY_NORMAL,
            ];
            $logger = new ConsoleLogger($output, $verbosityLevelMap);
            $client = new Websocket(
                $input->getOption('username') ?? $_SERVER['XTB_USERNAME'] ?? '',
                $input->getOption('password') ?? $_SERVER['XTB_PASSWORD'] ?? ''
            );
            $client->setLogger($logger);
            yield $client->connect();
            $client->registerPing();
            $client->keepAlive();
            foreach ($input->getOption('pair') as $symbol) {
                $pair = Pair::fromString($symbol);
                $client->registerPair($pair);
            }
            while ($message = yield $client->listen()) {
                $payload = yield $message->buffer();
                $tick = XTBTickPrice::fromString($payload);
                if ($tick instanceof TickPrice) {
                    $logger->info(
                        sprintf("Update price for symbol: %s [%.5f, %.5f]", $tick->pair()->symbol(), $tick->bid(), $tick->ask())
                    );
                    $this->rabbitMQ->write($tick);
                    $this->rabbitMQ->ack();
                }
            }
        });

        return Command::SUCCESS;
    }
}