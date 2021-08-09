<?php
declare(strict_types=1);
namespace CrazyGoat\Forex\ValueObject;

use CrazyGoat\Forex\Indicator\SimpleIndicatorAbstract;

class NamedIndicator
{
    private string $name;
    private SimpleIndicatorAbstract $indicator;

    public function __construct(string $name, SimpleIndicatorAbstract $indicator)
    {
        $this->name = $name;
        $this->indicator = $indicator;
    }

    public function name(): string{
        return $this->name;
    }

    public function indicator(): SimpleIndicatorAbstract
    {
        return $this->indicator;
    }
}