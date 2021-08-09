<?php

namespace CrazyGoat\Forex\Indicator;

use CrazyGoat\Forex\ValueObject\Candle;

abstract class SimpleIndicatorAbstract implements SimpleIndicatorInterface
{
    private CandleWindow $window;

    public function __construct(int $windowSize)
    {
        $this->window = new CandleWindow($windowSize);
    }

    public function append(Candle $candle): void
    {
        $this->window->append($candle);
    }

    public function calculate()
    {
        if ($this->ready()) {
            return $this->value();
        }

        return null;
    }

    public function ready(): bool
    {
        return $this->window->full();
    }

    protected function candles(): array
    {
        return $this->window->list();
    }

    protected function current(): Candle
    {
        return $this->last()[0];
    }

    protected function last(int $n = 1):array {
        $data = $this->window->list();
        return array_splice($data, -$n);
    }
}