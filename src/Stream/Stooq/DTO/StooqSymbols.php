<?php

declare(strict_types=1);

namespace CrazyGoat\Forex\Stream\Stooq\DTO;

use CrazyGoat\Forex\ValueObject\Pair;
use CrazyGoat\Forex\ValueObject\PairCollection;

class StooqSymbols
{
    /**
     * @return array<string>
     */
    public static function toStooqSymbols(PairCollection $collection): array
    {
        return array_map(
            function (Pair $pair): string {
                return StooqCurrencyMap::toStooq($pair);
            },
            $collection->list()
        );
    }
}