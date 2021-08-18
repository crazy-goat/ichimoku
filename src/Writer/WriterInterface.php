<?php
declare(strict_types=1);
namespace CrazyGoat\Forex\Writer;

use CrazyGoat\Forex\ValueObject\TickPrice;

interface WriterInterface
{
    //public static function createFromConfig(array $params): self;
    public function write(TickPrice $price);
}