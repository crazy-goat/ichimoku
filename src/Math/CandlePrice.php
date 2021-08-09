<?php

namespace CrazyGoat\Forex\Math;

use CrazyGoat\Forex\ValueObject\Candle;

class CandlePrice
{
    public const OPEN = 'open';
    public const CLOSE = 'close';
    public const HIGH = 'high';
    public const LOW = 'low';

    public static function price(Candle $candle, string $price = self::CLOSE)
    {
        return call_user_func([$candle, $price]);
    }
}