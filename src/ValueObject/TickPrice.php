<?php

declare(strict_types=1);

namespace CrazyGoat\Forex\ValueObject;

use DateTime;

class TickPrice implements \JsonSerializable
{
    public const DATE_FORMAT = 'Y-m-d H:i:s.v';
    
    private Pair $pair;
    private DateTime $time;
    private float $bid;
    private float $ask;

    public function __construct(Pair $pair, DateTime $time, float $bid, float $ask)
    {
        $this->pair = $pair;
        $this->time = $time;
        $this->bid = $bid;
        $this->ask = $ask;
    }

    public static function fromArray(array $data): TickPrice
    {
        return new TickPrice(
            Pair::fromString($data['symbol']),
            \DateTime::createFromFormat(self::DATE_FORMAT, $data['date'] ?? ''),
            $data['bid'] ?? null,
            $data['ask'] ?? null
        );
    }

    public function pair(): Pair
    {
        return $this->pair;
    }

    public function time(): DateTime
    {
        return $this->time;
    }

    public function formattedTime(): string
    {
        return $this->time->format(self::DATE_FORMAT);
    }

    public function bid(): float
    {
        return $this->bid;
    }

    public function ask(): float
    {
        return $this->ask;
    }

    public function jsonSerialize(): array
    {
        return [
            'symbol' => $this->pair->symbol(),
            'date' => $this->formattedTime(),
            'bid' => $this->bid,
            'ask' => $this->ask
            ];
    }
}