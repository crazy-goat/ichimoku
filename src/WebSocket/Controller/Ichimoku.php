<?php

declare(strict_types=1);

namespace CrazyGoat\Forex\WebSocket\Controller;

use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Status;
use Amp\Promise;
use CrazyGoat\Forex\Ichimoku\Signal\TKCross;
use CrazyGoat\Forex\Indicator\Ichimoku as IchimokuIndicator;
use CrazyGoat\Forex\Service\IchimokuFactory;
use CrazyGoat\Forex\ValueObject\Candle;
use CrazyGoat\Forex\ValueObject\IchimokuData;
use CrazyGoat\Forex\ValueObject\IchimokuData as IchimokuDataVO;
use CrazyGoat\Forex\ValueObject\IchimokuDataCollection;
use CrazyGoat\Forex\ValueObject\MultiPrice;
use CrazyGoat\Forex\ValueObject\Pair;
use CrazyGoat\Forex\ValueObject\Period;
use CrazyGoat\Forex\WebSocket\Database\Query\LatestCandles;
use CrazyGoat\Forex\WebSocket\DTO\Ichimoku as IchimokuDTO;

class Ichimoku implements RequestHandler
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

                    $pair = Pair::fromString($query['stocks'] ?? '');
                    $period = Period::fromString($query['period'] ?? 'H4');
                    $ichimoku = IchimokuFactory::factory($period);

                    $limit = (int) ($query['limit'] ?? '256') + $ichimoku->spanBLength() + $ichimoku->kijunLength();
                    $data = new IchimokuDataCollection(...($this->calculateIchimoku($pair, $period, $limit, $ichimoku)));

                    $tkCross = new TKCross($data);

                    return new Response(
                        Status::OK,
                        ['content-type' => 'application/json'],
                        json_encode(
                            [
                                'tenkan' => IchimokuDTO::tenkan($data),
                                'kijun' => IchimokuDTO::kijun($data),
                                'chikou' => IchimokuDTO::chikou(-$ichimoku->kijunLength(), $data),
                                'spanA' => IchimokuDTO::spanA($ichimoku->kijunLength(), $data),
                                'spanB' => IchimokuDTO::spanB($ichimoku->kijunLength(), $data),
                                'signals' => [...$tkCross->signals()]
                            ]
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

    /**
     * @param Pair              $pair
     * @param Period            $period
     * @param int               $limit
     * @param IchimokuIndicator $ichimoku
     * @param array             $data
     *
     * @return array
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    protected function calculateIchimoku(Pair $pair, Period $period, int $limit, IchimokuIndicator $ichimoku): \Generator
    {
        /** @var Candle $candle */
        foreach (($this->latestCandles)($pair, $period, $limit) as $candle) {
            $ichimoku->append($candle);
            $values = $ichimoku->calculate();
            if ($values instanceof MultiPrice) {
                yield new IchimokuDataVO(
                    $pair,
                    $candle->time(),
                    $period,
                    $values->value('tenkan'),
                    $values->value('kijun'),
                    $values->value('spanA'),
                    $values->value('spanB'),
                    $values->value('chikou')
                );
            }
        }
    }
}