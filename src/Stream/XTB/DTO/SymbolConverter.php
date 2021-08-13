<?php

declare(strict_types=1);

namespace CrazyGoat\Forex\Stream\XTB\DTO;

use CrazyGoat\Forex\ValueObject\Pair;

class SymbolConverter
{
    public static function fromXTB(string $symbol): Pair
    {
        return new Pair(substr($symbol, 0, 3), substr($symbol, 3));
    }
}