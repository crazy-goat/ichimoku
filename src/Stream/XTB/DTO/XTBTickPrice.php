<?php

namespace CrazyGoat\Forex\Stream\XTB\DTO;

use CrazyGoat\Forex\ValueObject\TickPrice;

class XTBTickPrice
{
    public static function fromString(string $value): ?TickPrice
    {
        $data = json_decode($value, true);
        if (isset($data['command']) && $data['command'] === 'tickPrices') {
            $data = $data['data'];

            return new TickPrice(
                SymbolConverter::fromXTB($data['symbol'] ?? ''),
                XTBTime::fromUnixTime((int) $data['timestamp']),
                (float) $data['bid'],
                (float) $data['ask']
            );
        }

        return null;
    }


}