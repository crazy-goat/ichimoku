<?php
declare(strict_types=1);
namespace CrazyGoat\Forex\ValueObject;

class Price
{
    private \DateTime $time;
    private float $price;

    public function __construct(\DateTime $time, float $price)
    {
        $this->time = clone $time;
        $this->price = $price;
    }

    public function time(): \DateTime
    {
        return $this->time;
    }

    public function price(): float
    {
        return $this->price;
    }
}