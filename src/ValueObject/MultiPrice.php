<?php

namespace CrazyGoat\Forex\ValueObject;

class MultiPrice
{

    /** @var NamedPrice[] */
    private array $prices;

    public function __construct(NamedPrice ...$prices)
    {
        foreach ($prices as $price) {
            $this->prices[$price->name()] = $price;
        }
    }

    public function value($name): float
    {
        return $this->prices[$name]->price();
    }
}