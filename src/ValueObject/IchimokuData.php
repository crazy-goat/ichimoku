<?php

declare(strict_types=1);

namespace CrazyGoat\Forex\ValueObject;

use DateTime;
use JsonSerializable;

class IchimokuData implements JsonSerializable
{
    private DateTime $time;
    private Period $period;
    private ?float $tenkan;
    private ?float $kijun;
    private ?float $spanA;
    private ?float $spanB;
    private ?float $chikou;
    private Pair $pair;

    public function __construct(
        Pair $pair,
        DateTime $time,
        Period $period,
        ?float $tenkan,
        ?float $kijun,
        ?float $spanA,
        ?float $spanB,
        ?float $chikou
    ) {
        $this->time = $time;
        $this->period = $period;
        $this->tenkan = $tenkan;
        $this->kijun = $kijun;
        $this->spanA = $spanA;
        $this->spanB = $spanB;
        $this->chikou = $chikou;
        $this->pair = $pair;
    }

    public static function fromArray($data): IchimokuData
    {
        return new IchimokuData(
            Pair::fromString($data['symbol']),
            \DateTime::createFromFormat(Candle::DATE_FORMAT, $data['date'] ?? ''),
            Period::fromString($data['period']),
            (float) $data['tenkan'],
            (float) $data['kijun'],
            (float) $data['spanA'],
            (float) $data['spanB'],
            (float) $data['chikou']
        );

    }

    public function tenkan(): ?float
    {
        return $this->tenkan;
    }

    public function kijun(): ?float
    {
        return $this->kijun;
    }

    public function spanA(): ?float
    {
        return $this->spanA;
    }

    public function spanB(): ?float
    {
        return $this->spanB;
    }

    public function chikou(): ?float
    {
        return $this->chikou;
    }

    public function pair(): Pair
    {
        return $this->pair;
    }

    public function jsonSerialize()
    {
        return [
            'symbol' => $this->pair->symbol(),
            'period' => $this->period->period(),
            'date' => $this->formattedTime(),
            'tenkan' => $this->tenkan,
            'kijun' => $this->kijun,
            'spanA' => $this->spanA,
            'spanB' => $this->spanB,
            'chikou' => $this->chikou
        ];
    }

    public function formattedTime(): string
    {
        return $this->time->format(Candle::DATE_FORMAT);
    }

    public function time(): DateTime
    {
        return $this->time;
    }

    public function period(): Period
    {
        return $this->period;
    }
}