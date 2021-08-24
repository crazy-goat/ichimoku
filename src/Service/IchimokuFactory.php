<?php
declare(strict_types=1);
namespace CrazyGoat\Forex\Service;

use CrazyGoat\Forex\Indicator\Ichimoku;
use CrazyGoat\Forex\ValueObject\Period;

class IchimokuFactory
{
    public static function factory(Period $period): Ichimoku
    {
        switch ($period->period()) {
            case Period::H4:
            case Period::H1:
                return new Ichimoku(7, 28, 119);
            default:
                return new Ichimoku();
        }
    }
}