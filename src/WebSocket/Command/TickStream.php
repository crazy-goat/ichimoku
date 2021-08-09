<?php
declare(strict_types=1);

namespace CrazyGoat\Forex\WebSocket\Command;

use Aerys\Console;
use Aerys\DebugProcess;
use Amp\Http\Server\HttpServer;
use Amp\Http\Server\Router;
use Amp\Loop;
use Amp\Promise;
use Amp\Socket\Server;
use Amp\Websocket\Server\Websocket;
use CrazyGoat\Forex\WebSocket\TickServer;
use GuzzleHttp\Handler\StreamHandler;
use League\CLImate\Logger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

class TickStream extends Command
{
    protected static $defaultName = 'forex:websocket:stream';
    private TickServer $websocket;

    public function __construct(TickServer $websocket)
    {
        parent::__construct();
        $this->websocket = $websocket;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        Loop::run(function () use ($output): Promise {
            $sockets = [
                Server::listen('127.0.0.1:1337'),
            ];

            $router = new Router();
            $router->addRoute('GET', '/stream', new Websocket($this->websocket));

            $logger = new ConsoleLogger($output);

            $server = new HttpServer($sockets, $router , $logger);

            return $server->start();
        });
    }
}