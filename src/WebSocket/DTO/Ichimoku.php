<?php

declare(strict_types=1);

namespace CrazyGoat\Forex\WebSocket\DTO;

use CrazyGoat\Forex\Service\PeriodTime;
use CrazyGoat\Forex\ValueObject\IchimokuData;
use CrazyGoat\Forex\ValueObject\IchimokuDataCollection;
use CrazyGoat\Forex\ValueObject\Price;
use DateInterval;
use DatePeriod;
use DateTime;
use DateTimeZone;

class Ichimoku
{
    public static function price(Price $price): array
    {
        return [
            'time' => (int) $price->time()->format('U'),
            'value' => $price->price()
        ];
    }

    /**
     * @return Price[]
     */
    public static function tenkan(IchimokuDataCollection $data): array
    {
        $tenkan = [];
        foreach ($data->list() as $ichimoku) {
            $tenkan[] = self::price(new Price($ichimoku->time(), $ichimoku->tenkan()));
        }

        return $tenkan;
    }

    public static function kijun(IchimokuDataCollection $data)
    {
        $kijun = [];
        foreach ($data->list() as $ichimoku) {
            $kijun[] = self::price(new Price($ichimoku->time(), $ichimoku->kijun()));
        }

        return $kijun;
    }

    public static function chikou(int $offset, IchimokuDataCollection $data): array
    {
        $chikou = [];
        foreach ($data->list() as $key => $current) {
            $future = $data->getByOffset($offset + $key);
            if ($future) {
                $chikou[] = self::price(new Price($future->time(), $current->chikou()));
            }
        }

        return $chikou;
    }

    public static function spanA(int $offset, IchimokuDataCollection $data): array
    {
        $spanA = [];
        $last = $data->last();
        foreach ($data->list() as $key => $current) {
            $future = $data->getByOffset($offset + $key);
            if ($future) {
                $spanA[] = self::price(new Price($future->time(), $current->spanA()));
            } else {
                $spanA[] = self::price(
                    new Price(self::offsetTime($last->time(), self::getOffsetSeconds($last, $offset + $key)), $current->spanA())
                );
            }
        }

        return $spanA;
    }

    public static function spanB(int $offset, IchimokuDataCollection $data): array
    {
        $spanB = [];
        $last = $data->last();
        foreach ($data->list() as $key => $current) {
            $future = $data->getByOffset($offset + $key);
            if ($future) {
                $spanB[] = self::price(new Price($future->time(), $current->spanB()));
            } else {
                $spanB[] = self::price(
                    new Price(self::offsetTime($last->time(), self::getOffsetSeconds($last, $offset + $key)), $current->spanB())
                );
            }
        }

        return $spanB;
    }

    public static function offsetTime(DateTime $time, int $offset): DateTime
    {
        return (new DateTime('now', new DateTimeZone('UTC')))->setTimestamp($time->getTimestamp() + $offset);
    }

    private static function getDateRange(IchimokuData $first, IchimokuData $last): DatePeriod
    {
        $interval = new DateInterval('PT' . PeriodTime::seconds($first->period()) . 'S');

        return new DatePeriod($first->time(), $interval, $last->time());
    }

    /**
     * @param IchimokuData $first
     * @param int          $offset
     *
     * @return float|int
     */
    protected static function getOffsetSeconds(IchimokuData $first, int $offset)
    {
        return PeriodTime::seconds($first->period()) * $offset;
    }
}