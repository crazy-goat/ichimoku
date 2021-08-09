<?php

namespace CrazyGoat\Forex\Indicator;

use CrazyGoat\Forex\Math\CandlePrice;
use CrazyGoat\Forex\Math\Highest as HighestPrice;
use CrazyGoat\Forex\Math\Lowest as LowestPrice;
use CrazyGoat\Forex\ValueObject\MultiPrice;
use CrazyGoat\Forex\ValueObject\NamedPrice;

class Ichimoku extends SimpleIndicatorAbstract
{
    private int $tekan;
    private int $kijun;
    private int $spanB;

    public function __construct(int $tekan = 9, int $kijun = 26, int $spanB = 56)
    {
        parent::__construct(max($tekan, $kijun, $spanB));
        $this->tekan = $tekan;
        $this->kijun = $kijun;
        $this->spanB = $spanB;
    }


    public function value()
    {
        $current = $this->current();
        $kijun = $this->mean($this->kijun);

        return new MultiPrice(
            new NamedPrice('tekan', $this->mean($this->tekan)),
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