<?php

namespace CrazyGoat\Forex\WebSocket;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Promise;
use Amp\Success;
use Amp\Websocket\Client;
use Amp\Websocket\Server\ClientHandler;
use Amp\Websocket\Server\Gateway;
use CrazyGoat\Forex\ValueObject\Pair;
use CrazyGoat\Forex\ValueObject\PairCollection;

use PhpAmqpLib\Connection\AMQPStreamConnection;

use function Amp\call;

class TickServer implements ClientHandler
{
    private AMQPStreamConnection $connection;

    public function __construct(AMQPStreamConnection $connection)
    {
        $this->connection = $connection;
    }

    public function handleHandshake(
        Gateway $gateway,
        Request $request,
        Response $response
    ): Promise {
        return new Success($response);
    }

    public function handleClient(
        Gateway $gateway,
        Client $client,
        Request $request,
        Response $response
    ): Promise {
        return call(function () use ($request, $client) {
            $rabbit = new WebSocketClient($this->connection, $client->getId(), $this->pairCollection($request));
            $client->onClose(
                function () use ($rabbit) {
                    $rabbit->close();
                }
            );

            $rabbit->consume(
                $client->getId(),
                function ($message) use ($client) {
                    $client->send($message->body);
                    $message->ack();
                }
            );

            return call(function () use ($client) {
                while ($message = yield $client->receive()) {
                    // Messages received on the connection are ignored and discarded.
                }
            });

        });
    }

    private function pairCollection(Request $request): PairCollection
    {
        parse_str($request->getUri()->getQuery(), $query);
        return new PairCollection(
            ...
            array_map(
                function (string $symbol) {
                    return new Pair(substr($symbol, 0, 3), substr($symbol, 3));
                },
                explode(',', $query['stocks'] ?? '')
            )
        );
    }
}