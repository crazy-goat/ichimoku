<?php

namespace CrazyGoat\Forex\Writer;

use CrazyGoat\Forex\ValueObject\TickPrice;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;

class MysqlTickWriter implements WriterInterface
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function write(TickPrice $price)
    {;
        if (!$this->connection->isTransactionActive()) {
            $this->connection->beginTransaction();
        }
        $this->connection->executeStatement(
            'INSERT INTO tick_data VALUES (:symbol, :time, :bid, :ask) ON DUPLICATE KEY UPDATE bid=:bid, ask=:ask',
            [
                'symbol' => $price->pair()->symbol(),
                'time' => $price->formattedTime(),
                'bid' => $price->bid(),
                'ask' => $price->ask()
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