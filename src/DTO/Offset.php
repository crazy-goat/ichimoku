<?php

namespace CrazyGoat\Forex\DTO;

class Offset
{
    public static function move(int $offset, ...$data): array
    {
        $mult = $offset < 0 ? 1 : -1;
        $len = count($data);
        $data = array_splice($data, -$offset);
        return array_pad($data,  $len* $mult, null);
    }
}