<?php

declare(strict_types=1);

namespace CrazyGoat\Forex\ValueObject;

class Pair
{
    private string $first;
    private string $second;

    public function __construct(string $first, string $second)
    {
        $this->first = $first;
        $this->second = $second;
    }

    public static function fromString(string $symbol): Pair
    {
        list($first, $second) = explode('/', $symbol);
        return new Pair($first, $second);
    }

    /**
     * @return string
     */
    public function first(): string
    {
        return $this->first;
    }

    /**
     * @return string
     */
    public function second(): string
    {
        return $this->second;
    }

    public function symbol(): string
    {
        return sprintf("%s/%s", $this->first, $this->second);
    }
}