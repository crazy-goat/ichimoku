<?php
declare(strict_types=1);
namespace CrazyGoat\Forex\ValueObject;

class Line
{
    private Point $first;
    private Point $second;

    public function __construct(Point $first, Point $second)
    {
        $this->first = $first;
        $this->second = $second;
    }

    /**
     * @return Point
     */
    public function first(): Point
    {
        return $this->first;
    }

    /**
     * @return Point
     */
    public function second(): Point
    {
        return $this->second;
    }
}