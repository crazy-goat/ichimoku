<?php

namespace CrazyGoat\Forex\DTO;

use CrazyGoat\Forex\ValueObject\Price;

class Offset
{
    public static function move(int $offset, ...$data): array
    {
        $mult = $offset < 0 ? 1 : -1;
        $len = count($data);
        $data = array_splice($data, -$offset);

        return array_pad($data, $len * $mult, null);
    }

    public static function movePrice(int $offset, Price $price): Price
    {
        return new Price(
            new \DateTime('@'.((int) $price->time()->format('U') + $offset)),
            $price->price()
        );
    }

    public static function movePrices(int $offset, Price ...$data): array
    {
        $result = [];
        foreach ($data as $price) {
            $result[] = self::movePrice($offset, $price);
        }

        return $result;
    }
}