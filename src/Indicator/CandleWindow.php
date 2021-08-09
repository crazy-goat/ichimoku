<?php

namespace CrazyGoat\Forex\Indicator;

use CrazyGoat\Forex\ValueObject\Candle;
use CrazyGoat\Forex\ValueObject\Window;

class CandleWindow extends Window
{
    public function append($value): ?Candle
    {
        if (!$value instanceof Candle) {
            throw new \InvalidArgumentException();
        }
        return parent::append($value);
    }

    /** @return Candle[] */
    public function list(): array
    {
        return parent::list();
    }
}