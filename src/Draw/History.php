<?php

namespace CrazyGoat\Forex\Draw;

use CrazyGoat\Forex\ValueObject\Candle;

class History
{
    private float $min;
    private float $max;
    /** @var Candle[] */
    private array $candles;
    private int $height;

    public function __construct(int $height, float $min, float $max, Candle ...$candles)
    {
        $this->min = $min;
        $this->max = $max;
        $this->candles = $candles;
        $this->height = $height;
    }

    public function drawValues(bool $print, ...$values)
    {

        $rowLen = count($this->candles);
        if (count($values)%$rowLen) {
            die('dadas');
        }

        $series = array_chunk($values, $rowLen);

        $data = $this->draw(false);
        //$data = str_pad("", $this->height * $rowLen);
        $step = ($this->max - $this->min) / $this->height;

        foreach ($series as $serie) {
            foreach (range(0, $rowLen - 1) as $x) {
                if (is_float($serie[$x] ?? null)) {
                    $y = (int) (($this->max - $serie[$x]) / $step);

                    if ($y >= 0 && $y < $this->height) {
                        $data[$y * $rowLen + $x] = '#';
                    }
                }
            }
        }

        return $data;
    }

    public function draw(bool $print): string
    {
        $rowLen = count($this->candles);
        $data = str_pad("", $this->height * $rowLen);

        $step = ($this->max - $this->min) / $this->height;

        foreach (range(0, $this->height - 1) as $y) {
            $priceLine = $this->max - ($step * $y);

            foreach ($this->candles as $x => $candle) {
                $data[$y * $rowLen + $x] = $this->drawCandle($candle, new Range($priceLine, $priceLine - $step));
            }
        }

        if ($print) {
            foreach (str_split($data, $rowLen) as $line) {
                echo $line . PHP_EOL;
            }
        }

        return $data;
    }

    private function drawCandle(Candle $candle, Range $price): string
    {
        $ret = ' ';
        $body = new Range($candle->open(), $candle->close());
        $stickH = new Range($candle->high(), max($candle->open(), $candle->close()));
        $stickL= new Range($candle->low(), min($candle->open(), $candle->close()));

        $common = Range::common($price, $stickH);
        if ($common instanceof Range) {
            $ret = "|";
        }

        $common = Range::common($price, $stickL);
        if ($common instanceof Range) {
            $ret = "I";
        }

        $bodyChar = $candle->close() > $candle->open() ? '/' : '\\';

        $common = Range::common($price, $body);
        if ($common instanceof Range) {
            $ret = $bodyChar;
        }

        return $ret;
    }
}