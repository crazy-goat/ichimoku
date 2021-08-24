<?php
declare(strict_types=1);
namespace CrazyGoat\Forex\ValueObject;

class Color
{
    private int $red;
    private int $green;
    private int $blue;
    private float $transparency;

    public function __construct(int $red, int $green, int $blue, float $transparency)
    {
        $this->red = $red;
        $this->green = $green;
        $this->blue = $blue;
        $this->transparency = $transparency;
    }

    public function hex()
    {
        return sprintf("#%02X%02X%02X",$this->red, $this->green, $this->blue);
    }
}