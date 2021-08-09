<?php

declare(strict_types=1);

namespace CrazyGoat\Forex\Stream\Oanda\DTO;

use CrazyGoat\Forex\ValueObject\Pair;

class SymbolConverter
{
    public static function toOanda(Pair $pair): string
    {
        return sprintf("%s/%s", strtoupper($pair->first()), $pair->second());
    }

    public static function fromOanda(string $pair): Pair
    {
        list($first, $second) = explode('/', $pair);

        return new Pair($first, $second);
    }
}