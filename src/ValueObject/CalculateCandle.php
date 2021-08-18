<?php

declare(strict_types=1);

namespace CrazyGoat\Forex\ValueObject;

class CalculateCandle
{
    private Candle $candle;
    private bool $calculated;

    public function __construct(Candle $candle, bool $calculated)
    {
        $this->candle = $candle;
        $this->calculated = $calculated;
    }

    /**
     * @return Candle
     */
    public function candle(): Candle
    {
        return $this->candle;
    }

    /**
     * @return bool
     */
    public function isCalculated(): bool
    {
        return $this->calculated;
    }
}