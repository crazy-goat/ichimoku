<?php

declare(strict_types=1);

namespace CrazyGoat\Forex\Stream\Oanda\DTO;

use CrazyGoat\Forex\ValueObject\TickPrice;
use CrazyGoat\Forex\ValueObject\TickPriceCollection;
use Exception;

class OandaTickPriceCollection
{
    /**
     * @throws Exception
     */
    public static function fromString(string $data): TickPriceCollection
    {
        $prices = preg_split('/\r\n|\r|\n/', $data);

        $prices = array_map(
            function (string $data): TickPrice {
                return OandaTickPrice::fromString($data);
            },
            $prices
        );

        return new TickPriceCollection(...$prices);
    }
}