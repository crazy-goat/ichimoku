<?php
declare(strict_types=1);

namespace CrazyGoat\Forex\Service;

class StringCounter
{
    /** @var array<string, int>  */
    private array $strings = [];

    public function add(string $value): int
    {
        $this->strings[$value] = $this->strings[$value] ?? 0;
        $this->strings[$value]++;

        return $this->strings[$value];
    }

    public function count(string $value): int
    {
        return $this->strings[$value] ?? 0;
    }
}