<?php
declare(strict_types=1);

namespace CrazyGoat\Forex\WebSocket\Command;

use Aerys\Console;
use Aerys\DebugProcess;
use Amp\Http\Server\HttpServer;
use Amp\Http\Server\Router;
use Amp\Http\Server\StaticContent\DocumentRoot;
use Amp\Loop;
use Amp\Promise;
use Amp\Socket\Server;
use Amp\Websocket\Server\Websocket;
use CrazyGoat\Forex\WebSocket\Controller\Candles;
use CrazyGoat\Forex\WebSocket\Controller\Ichimoku;
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
    private Candles $candlesController;
    private Ichimoku $ichimokuController;

    public function __construct(TickServer $websocket, Candles $candlesController, Ichimoku $ichimokuController)
    {
        parent::__construct();
        $this->websocket = $websocket;
        $this->candlesController = $candlesController;
        $this->ichimokuController = $ichimokuController;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        //Loop::run(function () use ($output): Promise {
        //    $router = new Router();
        //    $router->addRoute('GET', '/ticks', new Websocket($this->websocket));
        //
        //    $logger = new ConsoleLogger($output);
        //    $server = new HttpServer([Server::listen('0.0.0.0:1337')], $router , $logger);
        //
        //    return $server->start();
        //});

        Loop::run(function () use ($output): Promise {
            $documentRoot = new DocumentRoot(__DIR__ . '/../../../public');

            $router = new Router();
            $router->addRoute('GET', '/api/candles', $this->candlesController);
            $router->addRoute('GET', '/api/ichimoku', $this->ichimokuController);
            $router->setFallback($documentRoot);
            $logger = new ConsoleLogger($output);
            $server = new HttpServer([Server::listen('0.0.0.0:80')], $router , $logger);
            return $server->start();
        });
    }
}