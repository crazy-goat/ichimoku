<?php

namespace CrazyGoat\Forex\Indicator;

use CrazyGoat\Forex\ValueObject\Candle;

class Price
{
    public static function highest(Candle ...$candles): float
    {
        return array_reduce(
            $candles,
            function (float $carry, Candle $candle): float
            {
                return $candle->high() > $carry ? $candle->high() : $carry;
            },
            PHP_FLOAT_MIN
        );
    }

    public static function lowest(Candle ...$candles): float
    {
        return array_reduce(
            $candles,
            function (float $carry, Candle $candle): float
            {
                return $candle->low() < $carry ? $candle->low() : $carry;
            },
            PHP_FLOAT_MAX
        );
    }
}