<?php
declare(strict_types=1);
namespace CrazyGoat\Forex\Math;

use CrazyGoat\Forex\ValueObject\Line;

class LineIntersection
{
    public const NO_CROSS = 0;
    public const CROSS_OVER = 1;
    public const CROSS_UNDER = -1;

    public static function cross(Line $line1, Line $line2): int
    {
        if (self::crossOver($line1, $line2)) {
            return self::CROSS_OVER;
        }

        if (self::crossUnder($line1, $line2)) {
            return self::CROSS_UNDER;
        }

        return self::NO_CROSS;
    }

    public static function crossOver(Line $line1, Line $line2): bool
    {
        if ($line1->first()->y() > $line2->first()->y()) {
            return false;
        }

        return $line1->second()->y() > $line2->second()->y();
    }

    public static function crossUnder(Line $line1, Line $line2): bool
    {
        if ($line1->first()->y() < $line2->first()->y()) {
            return false;
        }

        return $line1->second()->y() < $line2->second()->y();
    }
}