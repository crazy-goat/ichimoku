<?php

declare(strict_types=1);

namespace CrazyGoat\Forex\Stream\Oanda\DTO;

use CrazyGoat\Forex\ValueObject\TickPrice;
use DateTime;
use Exception;

class OandaTickPrice
{
    /**
     * @throws Exception
     */
    public static function fromString(string $data): TickPrice
    {
        list($prices, $data) = explode(' / ', $data, 2);
        list($symbol, $bid, $ask) = explode('=', $prices);

        $pair = SymbolConverter::fromOanda($symbol);

        return new TickPrice($pair, new DateTime(), (float) $bid, (float) $ask);
    }
}