<?php

namespace CrazyGoat\Forex\Indicator;

use CrazyGoat\Forex\Math\CandlePrice;
use CrazyGoat\Forex\Math\Lowest as LowestPrice;

class Lowest extends SimpleIndicatorAbstract
{
    private string $price;

    public function __construct(int $len, string $price = CandlePrice::CLOSE)
    {
        parent::__construct($len);
        $this->price = $price;
    }

    public function value()
    {
        return LowestPrice::calculate($this->price, ...$this->candles());
    }
}