<?php

namespace CrazyGoat\Forex\Writer;

use CrazyGoat\Forex\ValueObject\Candle;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;

class MysqlCandleWriter
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public static function createFromConfig(array $params): MysqlCandleWriter
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
        return new MysqlCandleWriter($conn);
    }

    public function write(Candle $price)
    {;
        if (!$this->connection->isTransactionActive()) {
            $this->connection->beginTransaction();
        }
        $this->connection->executeStatement(
            'INSERT INTO candle_data VALUES (:symbol, :time, :period, :open, :high, :low, :close) 
                ON DUPLICATE KEY UPDATE open=:open, high=:high, low=:low, close=:close',
            [
                'symbol' => $price->pair()->symbol(),
                'time' => $price->formattedTime(),
                'period' => $price->period()->period(),
                'open' => $price->open(),
                'high' => $price->high(),
                'low' => $price->low(),
                'close' => $price->close(),
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