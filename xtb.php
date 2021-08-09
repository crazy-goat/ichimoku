<?php

require 'vendor/autoload.php';

use Amp\Websocket\Client;

$login = json_encode(
    [
        "command" => "login",
        "arguments" => [
            "userId" => "xxxxxx",
            "password" => "xxxxx",
        ],
        "customTag" => "my_login_command_id"
    ]
);

Amp\Loop::run(function () use ($login) {
    /** @var Client\Connection $connection */
    $connection = yield Client\connect('wss://ws.xtb.com/real');
    /** @var Client\Connection $connectionStream */
    $connectionStream = yield Client\connect('wss://ws.xtb.com/realStream');
    yield $connection->send($login);

    $message = yield (yield $connection->receive())->buffer();
    $sessionId = json_decode($message, true)['streamSessionId'];

    yield $connection->send(json_encode(['command' => 'getAllSymbols']));
    $message = yield (yield $connection->receive())->buffer();
    $result = (json_decode($message, true));

    $symbols = array_filter($result['returnData'] ?? [], function (array $symbol) {
        return ($symbol['categoryName'] ?? '') === 'FX';
    });
    $symbols = array_reduce(
        $symbols,
        function ($carry, $symbol) {
            $carry[$symbol['symbol']] = $symbol['description'];

            return $carry;
        },
        []
    );
    ksort($symbols);

    var_dump($symbols);
    Amp\Loop::stop();

    Amp\Loop::repeat(10000, function () use ($connection) {
        yield $connection->send(json_encode(['command' => 'ping']));
        $message = yield (yield $connection->receive())->buffer();
        printf("Received: %s\n", $message);
    });

    $connectionStream->send(
        json_encode(
            [
                'command' => 'getTickPrices',
                'streamSessionId' => $sessionId,
                'symbol' => 'EURUSD',
                'maxLevel' => 0,
                'minArrivalTime' => 1
            ]
        )
    );

    $connectionStream->send(
        json_encode(
            [
                'command' => 'getTickPrices',
                'streamSessionId' => $sessionId,
                'symbol' => 'USDCHF',
                'maxLevel' => 0,
                'minArrivalTime' => 1
            ]
        )
    );
    $connectionStream->send(json_encode(['command' => 'getKeepAlive', 'streamSessionId' => $sessionId]));


    while ($message = yield $connectionStream->receive()) {
        $payload = yield $message->buffer();
        printf("Received: %s\n", $payload);
    }
});