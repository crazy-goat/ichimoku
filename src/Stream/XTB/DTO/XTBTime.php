<?php

namespace CrazyGoat\Forex\Stream\XTB\DTO;

use CrazyGoat\Forex\ValueObject\TickPrice;

class XTBTime
{
    public static function fromUnixTime(int $value): \DateTime
    {
        $date = (new \DateTime())->setTimestamp(round($value / 1000.0));
        $micro = $value% 1000;
        $date->setTime((int) $date->format('H'), (int) $date->format('i'), (int) $date->format('s'), $micro * 1000);
        return $date;
    }
}