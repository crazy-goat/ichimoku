<?php
declare(strict_types=1);
namespace CrazyGoat\Forex\Service;

use CrazyGoat\Forex\ValueObject\Period;

class PeriodTime
{
    public static function seconds(Period $period): int
    {
        switch ($period->period()) {
            case Period::T: return 1;
            case Period::M1: return 60;
            case Period::M5: return 60 * 5;
            case Period::M15: return 60 * 15;
            case Period::M30: return 60 * 30;
            case Period::H1: return 60 * 60;
            case Period::H4: return 60 * 60 * 4;
            case Period::DAILY: return 60 * 60 * 24;
            case Period::WEEKLY: return 60 * 60 * 24 * 7;
            default: throw new \InvalidArgumentException('Not valid period');
        }
    }
}