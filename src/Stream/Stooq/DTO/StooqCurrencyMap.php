<?php

namespace CrazyGoat\Forex\Stream\Stooq\DTO;

use CrazyGoat\Forex\ValueObject\Pair;
use Exception;

class StooqCurrencyMap
{
    /**
     * @throws Exception
     */
    public static function fromStooq(string $stooqSymbol): Pair
    {
        if (substr($stooqSymbol, -1) === '1') {
            $symbol = substr(strtoupper($stooqSymbol), 0, strlen($stooqSymbol) - 1);

            return new Pair(substr($symbol, 0, 3), substr($symbol, 3));
        }
        throw new Exception('Not valid Stooq symbol');
    }

    public static function toStooq(Pair $pair): string
    {
        return strtolower($pair->first() . $pair->second()) . '1';
    }
}