<?php

declare(strict_types=1);

namespace CrazyGoat\Forex\WebSocket\Controller;

use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Status;
use Amp\Promise;
use CrazyGoat\Forex\ValueObject\Pair;
use CrazyGoat\Forex\ValueObject\Period;
use CrazyGoat\Forex\WebSocket\Database\Query\LatestCandles;
use CrazyGoat\Forex\WebSocket\DTO\TradingView;

class Candles implements RequestHandler
{
    private LatestCandles $latestCandles;

    public function __construct(LatestCandles $latestCandles)
    {
        $this->latestCandles = $latestCandles;
    }

    public function handleRequest(Request $request): Promise
    {
        return \Amp\call(
            function () use ($request) {
                try {
                    parse_str($request->getUri()->getQuery(), $query);

                    $symbol = Pair::fromString($query['stocks'] ?? '');
                    $period = Period::fromString($query['period'] ?? 'H4');
                    $limit = (int) ($query['limit'] ?? '256');

                    return new Response(
                        Status::OK,
                        ['content-type' => 'application/json'],
                        json_encode(
                            TradingView::fromCandles(
                                ...($this->latestCandles)($symbol, $period, $limit)
                            )
                        )
                    );
                } catch (\Throwable $exception) {
                    return new Response(
                        Status::OK,
                        ['content-type' => 'text/html'],
                        '<h1>' . $exception->getMessage() . '</h1><pre>' . $exception->getTraceAsString() . '</pre>'
                    );
                }
            }
        );
    }
}