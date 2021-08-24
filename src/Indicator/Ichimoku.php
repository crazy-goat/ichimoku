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
        $tenkan = $this->mean($this->tenkan);

        return new MultiPrice(
            new NamedPrice('tenkan', $tenkan),
            new NamedPrice('kijun', $kijun),
            new NamedPrice('spanA', ($tenkan + $kijun) / 2),
            new NamedPrice('spanB', $this->mean($this->spanB)),
            new NamedPrice('chikou', $current->close())
        );
    }

    private function mean(int $n): float
    {
        return (HighestPrice::calculate(CandlePrice::HIGH, ...$this->last($n)) +
                   LowestPrice::calculate(CandlePrice::LOW, ...$this->last($n))) / 2;

    }

    /**
     * @return int
     */
    public function tenkanLength(): int
    {
        return $this->tenkan;
    }

    /**
     * @return int
     */
    public function kijunLength(): int
    {
        return $this->kijun;
    }

    /**
     * @return int
     */
    public function spanBLength(): int
    {
        return $this->spanB;
    }
}