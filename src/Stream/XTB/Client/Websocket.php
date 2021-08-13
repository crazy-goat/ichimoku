<?php

namespace CrazyGoat\Forex\Stream\XTB\Client;

use Amp\Promise;
use Amp\Websocket\Client;
use CrazyGoat\Forex\ValueObject\Pair;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

use function Amp\call;

class Websocket implements LoggerAwareInterface
{
    private string $user;
    private string $password;
    private ?Client\Connection $connection = null;
    private ?Client\Connection $connectionStream = null;
    private ?string $sessionId = null;
    private LoggerInterface $logger;


    public function __construct(string $user, string $password)
    {
        $this->user = $user;
        $this->password = $password;
    }

    public function symbols(): Promise
    {
        return call(function () {
            yield $this->connect();
            yield $this->connection->send(json_encode(['command' => 'getAllSymbols']));
            $message = yield (yield $this->connection->receive())->buffer();
            $result = (json_decode($message, true));

            $symbols = array_filter($result['returnData'] ?? [], function (array $symbol) {
                return ($symbol['categoryName'] ?? '') === 'FX';
            });

            return array_reduce(
                $symbols,
                function ($carry, $symbol) {
                    $carry[sprintf("%s/%s", $symbol['currency'], $symbol['currencyProfit'])] = $symbol['description'];

                    return $carry;
                },
                []
            );
        });
    }

    public function connect(): Promise
    {
        return call(function () {
            if ($this->sessionId === null) {
                $this->connection = yield Client\connect('wss://ws.xtb.com/real');
                $this->logger->info('Connecting to: wss://ws.xtb.com');
                yield $this->connection->send(
                    json_encode(
                        [
                            "command" => "login",
                            "arguments" => [
                                "userId" => $this->user,
                                "password" => $this->password,
                            ],
                            "customTag" => "my_login_command_id"
                        ]
                    )
                );
                $message = yield (yield $this->connection->receive())->buffer();
                $this->sessionId = json_decode($message, true)['streamSessionId'] ?? null;
                if ($this->sessionId === null) {
                    throw new \Exception('Could not connect to xtb server');
                }
                $this->logger->info('Connected successfully. Session id: ' . $this->sessionId);

                $this->logger->info('Connecting to: wss://ws.xtb.com/realStream');
                $this->connectionStream = yield Client\connect('wss://ws.xtb.com/realStream');
                $this->logger->info('Connected successfully.');
            }

            return true;
        });
    }

    public function registerPing($repeat = 10000): string
    {
        return \Amp\Loop::repeat($repeat, function () {
            $this->connection->send(json_encode(['command' => 'ping']));
            $message = yield (yield $this->connection->receive())->buffer();
            $this->logger->info("Ping status: " . $message);
        });
    }


    public function registerPair(Pair $pair)
    {
        $this->connectionStream->send(
            json_encode(
                [
                    'command' => 'getTickPrices',
                    'streamSessionId' => $this->sessionId,
                    'symbol' => $pair->first() . $pair->second(),
                    'maxLevel' => 0,
                    'minArrivalTime' => 1
                ]
            )
        )->onResolve(function () use ($pair) {
            $this->logger->info('Register to listen on pair: '.$pair->symbol());
        });
    }

    public function keepAlive()
    {
        $this->connectionStream
            ->send(json_encode(['command' => 'getKeepAlive', 'streamSessionId' => $this->sessionId]))
            ->onResolve(function () {
                $this->logger->info('Set keep alive to true');
            });
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function listen(): Promise
    {
        return $this->connectionStream->receive();
    }
}