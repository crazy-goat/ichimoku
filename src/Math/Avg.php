<?php

namespace CrazyGoat\Forex\Math;

use CrazyGoat\Forex\ValueObject\Candle;

class Avg
{
    public static function calculate(string $price, Candle ...$candles): float
    {
        return Sum::calculate($price, ...$candles)/count($candles);
    }
}