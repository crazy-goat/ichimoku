<?php

declare(strict_types=1);

namespace CrazyGoat\Forex\ValueObject;

class TickPriceCollection
{
    /** @var TickPrice[] */
    private array $prices;

    public function __construct(TickPrice ...$prices)
    {
        $this->prices = $prices;
    }

    /**
     * @return TickPrice[]
     */
    public function prices(): array
    {
        return $this->prices;
    }
}