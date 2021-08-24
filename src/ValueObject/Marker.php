<?php
declare(strict_types=1);
namespace CrazyGoat\Forex\ValueObject;

class Marker implements \JsonSerializable
{
    private \DateTime $time;
    private Position $position;
    private Shape $shape;
    private Color $color;

    public function __construct(\DateTime $time, Position $position, Shape $shape, Color $color)
    {
        $this->time = $time;
        $this->position = $position;
        $this->shape = $shape;
        $this->color = $color;
    }

    public function jsonSerialize()
    {
        return [
            'time' => (int) $this->time->format('U'),
            'position' => $this->position->position(),
            'color' => $this->color->hex(),
            'shape' => $this->shape->shape()
        ];
    }
}