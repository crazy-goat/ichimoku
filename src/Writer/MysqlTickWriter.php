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

    public static function createFromConfig(array $params): MysqlTickWriter
    {
        $connectionParams = array(
            'dbname' => 'fx_prices',
            'user' => 'root',
            'password' => 'root',
            'host' => '127.0.0.1',
            'port' => 6033,
            'driver' => 'pdo_mysql',
        );
        $conn = DriverManager::getConnection($connectionParams);
        return new MysqlTickWriter($conn);
    }

    public function write(TickPrice $price)
    {;
        if (!$this->connection->isTransactionActive()) {
            $this->connection->beginTransaction();
        }
        //var_dump($price->formattedTime());
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