<?php

namespace CrazyGoat\Forex\ValueObject;

class Candle implements \JsonSerializable
{
    public const DATE_FORMAT = 'Y-m-d H:i:s.v';

    private Pair $pair;
    private Period $period;
    private float $open;
    private float $close;
    private float $high;
    private float $low;
    private \DateTime $time;

    public function __construct(Pair $pair, Period $period, \DateTime $time, float $open, float $high, float $low, float $close)
    {
        $this->pair = $pair;
        $this->period = $period;
        $this->open = $open;
        $this->close = $close;
        $this->high = $high;
        $this->low = $low;
        $this->time = $time;
    }

    public static function fromArray(array $data): Candle
    {
        return new Candle(
            Pair::fromString($data['symbol'] ?? null),
            new Period($data['period'] ?? null),
            \DateTime::createFromFormat(self::DATE_FORMAT, $data['date'] ?? ''),
            (float) $data['open'],
            (float) $data['high'],
            (float) $data['low'],
            (float) $data['close']
        );
    }

    public function pair(): Pair
    {
        return $this->pair;
    }

    public function period(): Period
    {
        return $this->period;
    }

    public function open(): float
    {
        return $this->open;
    }

    public function close(): float
    {
        return $this->close;
    }

    public function high(): float
    {
        return $this->high;
    }

    public function low(): float
    {
        return $this->low;
    }

    public function time(): \DateTime
    {
        return $this->time;
    }

    public function formattedTime(): string
    {
        return $this->time->format(self::DATE_FORMAT);
    }

    public function jsonSerialize(): array
    {
        return [
            'symbol' => $this->pair->symbol(),
            'date' => $this->formattedTime(),
            'period' => $this->period->period(),
            'open' => $this->open,
            'high' => $this->high,
            'low' => $this->low,
            'close' => $this->close
        ];
    }
}