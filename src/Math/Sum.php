<?php

namespace CrazyGoat\Forex\Math;

use CrazyGoat\Forex\ValueObject\Candle;

class Sum
{
    public static function calculate(string $price, Candle ...$candles): float
    {
        return array_reduce(
            $candles,
            function (float $sum, Candle $candle) use ($price) {
                return $sum + CandlePrice::price($candle, $price);
            },
            0.0
        );
    }
}