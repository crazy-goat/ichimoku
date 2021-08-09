<?php

namespace CrazyGoat\Forex\Draw;

class Range
{
    private float $start;
    private float $end;

    public function __construct(float $start, float $end)
    {
        $this->start = min($start, $end);
        $this->end = max($start, $end);
    }

    public static function common(Range $first, Range $second): ?Range
    {
        $start = max($first->start(), $second->start());
        $end = min($first->end(), $second->end);

        return $start <= $end ? new Range($start, $end) : null;
    }

    public function start(): float
    {
        return $this->start;
    }

    public function end(): float
    {
        return $this->end;
    }

    public function len(): float
    {
        return $this->start - $this->end;
    }
}