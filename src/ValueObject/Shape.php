<?php
declare(strict_types=1);
namespace CrazyGoat\Forex\ValueObject;

class Shape
{
    public const CIRCLE = 'circle';
    public const SQUARE = 'square';
    public const ARROW_UP = 'arrowUp';
    public const ARROW_DOWN = 'arrowDown';

    private const VALID_VALUES = [self::CIRCLE, self::SQUARE, self::ARROW_DOWN, self::ARROW_UP];
    private string $shape;

    public function __construct(string $shape)
    {
        if (!in_array($shape, self::VALID_VALUES, true)) {
            throw new \InvalidArgumentException('Invalid shape "'.$shape.'", valid values'.implode(',', self::VALID_VALUES));
        }

        $this->shape = $shape;
    }

    public function shape(): string
    {
        return $this->shape;
    }

}