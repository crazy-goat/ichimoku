<?php
declare(strict_types=1);
namespace CrazyGoat\Forex\WebSocket\DTO;

use CrazyGoat\Forex\ValueObject\Candle;
use CrazyGoat\Forex\ValueObject\IchimokuData;

class TradingView
{
    public static function fromCandle(Candle $candle): array
    {
        return [
            'time' => (int)$candle->time()->format('U'),
            'open' => $candle->open(),
            'high' => $candle->high(),
            'low' => $candle->low(),
            'close' => $candle->close(),
        ];
    }

    public static function fromCandles(Candle ...$candles): array
    {
        $data = [];
        foreach ($candles as $candle) {
            $data[] = self::fromCandle($candle);
        }
        return $data;
    }

    public static function fromIchimoku(IchimokuData $ichimoku): array
    {
        return [
            'time' => (int)$ichimoku->time()->format('U'),
            'kijun' => $ichimoku->kijun(),
            'tenkan' => $ichimoku->tenkan(),
            'spanA' => $ichimoku->spanA(),
            'spanB' => $ichimoku->spanB(),
            'chikou' => $ichimoku->chikou(),
        ];
    }

    public static function fromIchimokus(IchimokuData ...$ichimokus): array
    {
        $data = [];
        foreach ($ichimokus as $ichimoku) {
            $data[] = self::fromIchimoku($ichimoku);
        }
        return $data;
    }
}