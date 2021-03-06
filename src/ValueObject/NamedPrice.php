<?php
declare(strict_types=1);
namespace CrazyGoat\Forex\ValueObject;

class NamedPrice
{
    private string $name;
    private float $price;

    public function __construct(string $name, float $price)
    {
        $this->name = $name;
        $this->price = $price;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function price(): float
    {
        return $this->price;
    }
}