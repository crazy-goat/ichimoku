<?php
declare(strict_types=1);
namespace CrazyGoat\Forex\Reader;

interface ReaderInterface
{
    public static function createFromConfig(array $params): self;
    public function read(): \Generator;
}