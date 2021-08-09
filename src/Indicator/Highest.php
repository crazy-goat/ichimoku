<?php

namespace CrazyGoat\Forex\Indicator;

use CrazyGoat\Forex\Math\CandlePrice;
use CrazyGoat\Forex\Math\Highest as HighestPrice;
use CrazyGoat\Forex\ValueObject\Candle;

class Highest extends SimpleIndicatorAbstract
{
    private string $price;

    public function __construct(int $len, string $price = CandlePrice::CLOSE)
    {
        parent::__construct($len);
        $this->price = $price;
    }

    public function value()
    {
        return HighestPrice::calculate($this->price, ...$this->candles());
    }
}