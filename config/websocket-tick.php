<?php

use CrazyGoat\Forex\Kernel;

use CrazyGoat\Forex\WebSocket\TickServer;

use function Aerys\websocket;

if (!is_file(dirname(__DIR__).'/vendor/autoload_runtime.php')) {
    throw new LogicException('Symfony Runtime is missing. Try running "composer require symfony/runtime".');
}

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

$kernel = new Kernel('prod', false);
$kernel->boot();
$websocket = websocket($kernel->getContainer()->get(TickServer::class));
$router = (new Aerys\Router)->route('GET', '/stream', $websocket);

return (new Aerys\Host)->expose("127.0.0.1", 1337)->use($router);

