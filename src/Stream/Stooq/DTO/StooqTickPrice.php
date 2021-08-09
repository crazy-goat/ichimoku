<?php

declare(strict_types=1);

namespace CrazyGoat\Forex\Stream\Stooq\DTO;

use CrazyGoat\Forex\ValueObject\TickPrice;
use DateTime;
use Exception;

class StooqTickPrice
{
    /**
     * @throws Exception
     */
    public static function fromString(string $data): TickPrice
    {
        $items = explode(' ', $data, 5);
        $symbol = StooqCurrencyMap::fromStooq($items[0]);
        $dateTime = DateTime::createFromFormat('YmdHis', $items[1] . $items[2]);
        $price = (float) $items[4];

        return new TickPrice($symbol, $dateTime, $price, $price);
    }
}