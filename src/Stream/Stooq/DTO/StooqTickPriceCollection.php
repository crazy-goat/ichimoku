<?php

declare(strict_types=1);

namespace CrazyGoat\Forex\Stream\Stooq\DTO;

use CrazyGoat\Forex\ValueObject\TickPrice;
use CrazyGoat\Forex\ValueObject\TickPriceCollection;
use Exception;

class StooqTickPriceCollection
{
    /**
     * @throws Exception
     */
    public static function fromString(string $data): TickPriceCollection
    {
        if (strlen(trim($data, ":")) === 0) {
            return new TickPriceCollection();
        }

        if (strpos($data, 'data:') === false) {
            throw new Exception('Data row must start with "data: "');
        }
        $prices = explode('|', trim(substr($data, 6), '|'));

        $prices = array_map(
            function (string $data): TickPrice {
                return StooqTickPrice::fromString($data);
            },
            $prices
        );

        return new TickPriceCollection(...$prices);
    }
}