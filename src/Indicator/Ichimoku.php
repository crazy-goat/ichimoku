<?php

namespace CrazyGoat\Forex\Indicator;

use CrazyGoat\Forex\Math\CandlePrice;
use CrazyGoat\Forex\Math\Highest as HighestPrice;
use CrazyGoat\Forex\Math\Lowest as LowestPrice;
use CrazyGoat\Forex\ValueObject\MultiPrice;
use CrazyGoat\Forex\ValueObject\NamedPrice;

class Ichimoku extends SimpleIndicatorAbstract
{
    private int $tenkan;
    private int $kijun;
    private int $spanB;

    public function __construct(int $tenkan = 9, int $kijun = 26, int $spanB = 52)
    {
        parent::__construct(max($tenkan, $kijun, $spanB));
        $this->tenkan = $tenkan;
        $this->kijun = $kijun;
        $this->spanB = $spanB;
    }


    public function value()
    {
        $current = $this->current();
        $kijun = $this->mean($this->kijun);

        return new MultiPrice(
            new NamedPrice('tenkan', $this->mean($this->tenkan)),
            new NamedPrice('kijun', $kijun),
            new NamedPrice('spanA', ($current->close() + $kijun) / 2),
            new NamedPrice('spanB', $this->mean($this->spanB)),
            new NamedPrice('chikou', $current->close())
        );
    }

    private function mean(int $n): float
    {
        return (HighestPrice::calculate(CandlePrice::CLOSE, ...$this->last($n)) +
                   LowestPrice::calculate(CandlePrice::CLOSE, ...$this->last($n))) / 2;

    }
}