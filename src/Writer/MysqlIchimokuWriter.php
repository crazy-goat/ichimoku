<?php

namespace CrazyGoat\Forex\Writer;

use CrazyGoat\Forex\ValueObject\IchimokuData;
use CrazyGoat\Forex\ValueObject\TickPrice;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;

class MysqlIchimokuWriter
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function write(IchimokuData $price)
    {;
        if (!$this->connection->isTransactionActive()) {
            $this->connection->beginTransaction();
        }
        $this->connection->executeStatement(
            'INSERT INTO ichimoku_data VALUES (:symbol, :time, :period, :tenkan, :kijun, :spanA, :spanB, :chikou) 
                ON DUPLICATE KEY UPDATE tenkan=:tenkan, kijun=:kijun, span_a=:spanA, span_b=:spanB, chikou=:chikou',
            [
                'symbol' => $price->pair()->symbol(),
                'time' => $price->formattedTime(),
                'period' => $price->period()->period(),
                'tenkan' => $price->tenkan(),
                'kijun' => $price->kijun(),
                'spanA' => $price->spanA(),
                'spanB' => $price->spanB(),
                'chikou' => $price->chikou()
            ]
        );

        $this->connection->executeStatement(
            'UPDATE candle_data SET ichimoku=1 WHERE symbol=:symbol AND period=:period AND time=:time',
            [
                'symbol' => $price->pair()->symbol(),
                'time' => $price->formattedTime(),
                'period' => $price->period()->period(),
            ]
        );
    }

    public function ack()
    {
        if ($this->connection->isTransactionActive()) {
            $this->connection->commit();
        }
    }

    public function nack()
    {
        if ($this->connection->isTransactionActive()) {
            $this->connection->rollBack();
        }
    }
}