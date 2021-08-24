<?php
declare(strict_types=1);
namespace CrazyGoat\Forex\Ichimoku\Signal;

use CrazyGoat\Forex\Indicator\CandleWindow;
use CrazyGoat\Forex\Math\LineIntersection;
use CrazyGoat\Forex\ValueObject\Color;
use CrazyGoat\Forex\ValueObject\IchimokuData;
use CrazyGoat\Forex\ValueObject\IchimokuDataCollection;
use CrazyGoat\Forex\ValueObject\Line;
use CrazyGoat\Forex\ValueObject\Marker;
use CrazyGoat\Forex\ValueObject\Point;
use CrazyGoat\Forex\ValueObject\Position;
use CrazyGoat\Forex\ValueObject\Shape;
use CrazyGoat\Forex\ValueObject\Window;

class TKCross
{
    private IchimokuDataCollection $data;

    public function __construct(IchimokuDataCollection $data)
    {
        $this->data = $data;
    }

    /**
     * @return \Generator|Marker[]
     */
    public function signals(): \Generator
    {
        $window = new Window(2);
        foreach ($this->data->list() as $value) {
            $window->append($value);
            if ($window->full()) {
                /** @var IchimokuData $prev */
                /** @var IchimokuData $current */
                list($prev, $current) = $window->list();
                $cross = LineIntersection::cross(
                    new Line(new Point(0, $prev->kijun()), new Point(1, $current->kijun())),
                    new Line(new Point(0, $prev->tenkan()), new Point(1, $current->tenkan()))
                );

                if ($cross != LineIntersection::NO_CROSS) {
                    yield new Marker(
                        $current->time(),
                        new Position($cross === LineIntersection::CROSS_OVER ? Position::ABOVE_BAR : Position::BELOW_BAR),
                        new Shape($cross === LineIntersection::CROSS_OVER ? Shape::ARROW_DOWN : Shape::ARROW_UP),
                        $cross === LineIntersection::CROSS_OVER ? new Color(255,0,0,1) : new Color(0,0,255,1)
                    );
                }
            }
        }
    }
}