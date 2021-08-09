<?php

namespace CrazyGoat\Forex\DTO;

use CrazyGoat\Forex\ValueObject\MultiPrice;

class PriceArray
{
    public static function fromNamedMultiPrice(string $name, ...$prices): array
    {
        $data = [];
        foreach ($prices as $price) {
            if ($price instanceof MultiPrice) {
                $data[] = $price->value($name);
            } else {
                $data[] = null;
            }
        }

        return $data;
    }
}