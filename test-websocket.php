#!/usr/bin/env php
<?php

use Aerys\Request;
use Aerys\Response;
use Aerys\Websocket;
use Amp\Loop;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exchange\AMQPExchangeType;

use function Aerys\websocket;

require __DIR__ . '/vendor/autoload.php';
$websocket = websocket(
    new class implements Websocket {
        private $endpoint;
        private $channel;
        private $connection;
        private $queue;
        private $cancel;

        private function createClient(int $clientId, array $handshakeData)
        {
            $this->queue[$clientId] = uniqid('client_') . uniqid('-');
            $exchange = 'prices.websocket';

            $this->connection[$clientId] = new AMQPStreamConnection('rabbitmq.local', 5672, 'rabbitmq', 'rabbitmq');
            $this->channel[$clientId] = $this->connection[$clientId]->channel();
            $this->channel[$clientId]->queue_declare($this->queue[$clientId], false, false, true);
            $this->channel[$clientId]->exchange_declare($exchange, AMQPExchangeType::DIRECT, true);
            $this->channel[$clientId]->basic_qos(null, 1, null);
            foreach ($handshakeData as $pair) {
                $this->channel[$clientId]->queue_bind($this->queue[$clientId], $exchange, $pair);
            }
        }

        public function onStart(Websocket\Endpoint $endpoint)
        {
            $this->endpoint = $endpoint;
        }

        public function onHandshake(Request $request, Response $response)
        {
            return explode(',', $request->getParam('stocks') ?? '');
        }

        public function onOpen(int $clientId, $handshakeData)
        {
            $this->createClient($clientId, $handshakeData);

            $this->channel[$clientId]->basic_consume($this->queue[$clientId], $clientId, false, false, false, false,
                function ($message) use ($clientId) {
                    $this->endpoint->send($message->body, $clientId);
                    $message->ack();
                }
            );

            $this->cancel[$clientId] = Loop::repeat(10, function () use ($clientId) {
                $this->channel[$clientId]->wait(null, true);
            });
        }

        public function onData(int $clientId, Websocket\Message $msg)
        {
        }

        public function onClose(int $clientId, int $code, string $reason)
        {
            Loop::cancel($this->cancel[$clientId]);
            $this->channel[$clientId]->close();
            $this->connection[$clientId]->close();
            unset($this->connection[$clientId]);
            unset($this->channel[$clientId]);
            unset($this->queue[$clientId]);
        }

        public function onStop()
        {
        }
    }
);
$router = (new Aerys\Router)
    ->route('GET', '/websocket', $websocket)
    ->route(
        'GET',
        '/',
        function (Aerys\Request $req, Aerys\Response $res) {
            $res->end(
                '<pre id="log"></pre>
<script type="text/javascript">
    ws = new WebSocket("ws://localhost:1337/websocket?stocks=' . $req->getParam('stocks') . '");
    ws.onopen = function() {
        ws.send("ping");
    };
    ws.onmessage = function(e) {
        document.getElementById("log").innerHTML += e.data +"\n";
    };
</script>'
            );
        }
    );

return (new Aerys\Host)->expose("127.0.0.1", 1337)->use($router);