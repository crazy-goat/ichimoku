<?php
declare(strict_types=1);
namespace CrazyGoat\Forex\Math;

use CrazyGoat\Forex\ValueObject\Candle;

class Highest
{
    public static function calculate(string $price, Candle ...$candles): float
    {
        return array_reduce(
            $candles,
            function (float $high, Candle $candle) use ($price): float {
                return max(CandlePrice::price($candle, $price), $high);
            },
            PHP_FLOAT_MIN
        );
    }
}