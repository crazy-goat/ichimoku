<?php

namespace CrazyGoat\Forex\Download\Histdata\DTO;

use CrazyGoat\Forex\ValueObject\Period;

class HistdataPeriod
{
    public static function fromPeriod(Period $period): string
    {
        switch ($period->period()) {
            case Period::T: return 'T';
            case Period::M1: return 'M1';
            default:
                throw new \InvalidArgumentException('Period not found');
        }
    }
}