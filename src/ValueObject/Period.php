<?php

declare(strict_types=1);

namespace CrazyGoat\Forex\ValueObject;

use CrazyGoat\Forex\Service\AllPeriods;

class Period
{
    use AllPeriods;

    public const T = 'T';
    public const M1 = '1';
    public const M5 = '5';
    public const M15 = '15';
    public const M30 = '30';
    public const H1 = 'H1';
    public const H4 = 'H4';
    public const DAILY = 'D';
    public const WEEKLY = 'W';

    private string $period;

    public function __construct(string $period)
    {
        $this->period = $period;
    }

    public static function fromString(string $value): Period
    {
        if (!in_array($value, self::getAllPeriodsCodes(), true)) {
            throw new \InvalidArgumentException('Not valid period');
        }

        return new Period($value);
    }

    public function period(): string
    {
        return $this->period;
    }
}