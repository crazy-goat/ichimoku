<?php

namespace CrazyGoat\Forex\ValueObject;

class Position
{
    public const ABOVE_BAR = 'aboveBar';
    public const BELOW_BAR = 'belowBar';
    public const IN_BAR = 'inBar';

    private const VALID_VALUES = [self::ABOVE_BAR, self::BELOW_BAR, self::IN_BAR];
    private string $position;

    public function __construct(string $position)
    {
        if (!in_array($position, self::VALID_VALUES, true)) {
            throw new \InvalidArgumentException('Invalid position "'.$position.'", valid values'.implode(',', self::VALID_VALUES));
        }

        $this->position = $position;
    }

    /**
     * @return string
     */
    public function position(): string
    {
        return $this->position;
    }
}