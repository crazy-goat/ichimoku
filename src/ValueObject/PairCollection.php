<?php

declare(strict_types=1);

namespace CrazyGoat\Forex\ValueObject;

class PairCollection
{
    /** @var Symbol[] */
    private array $symbols;

    public function __construct(Pair ...$symbols)
    {
        $this->symbols = $symbols;
    }

    public function list(): array
    {
        return $this->symbols;
    }
}