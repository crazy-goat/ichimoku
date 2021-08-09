<?php
declare(strict_types=1);
namespace CrazyGoat\Forex\Indicator;

use CrazyGoat\Forex\Math\Avg;
use CrazyGoat\Forex\Math\CandlePrice;

class SMA extends SimpleIndicatorAbstract
{
    private string $price;

    public function __construct(int $len, string $price = CandlePrice::CLOSE)
    {
        parent::__construct($len);
        $this->price = $price;
    }

    public function value(): ?float
    {
        if ($this->ready()) {
            return Avg::calculate($this->price, ...$this->candles());
        }
        return null;
    }
}