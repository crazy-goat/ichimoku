<?php
declare(strict_types=1);
namespace CrazyGoat\Forex\Indicator;

interface SimpleIndicatorInterface
{
    /** @return mixed */
    public function value();
}