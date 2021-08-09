<?php

namespace CrazyGoat\Forex\Service;

use CrazyGoat\Forex\ValueObject\Period;

use function Amp\Promise\rethrow;

trait AllPeriods
{
    /** @return Period[] */
    protected static function getAllPeriods(): array
    {
        return [
            new Period(Period::WEEKLY),
            new Period(Period::DAILY),
            new Period(Period::H4),
            new Period(Period::H1),
            new Period(Period::M30),
            new Period(Period::M15),
            new Period(Period::M5),
            new Period(Period::M1),
            new Period(Period::T)
        ];
    }

    protected static function getAllPeriodsCodes(): array
    {
        return array_map(
            function (Period $period): string {
                return $period->period();
            },
            self::getAllPeriods()
        );
    }
}