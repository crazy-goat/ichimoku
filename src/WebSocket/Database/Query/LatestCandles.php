<?php
declare(strict_types=1);
namespace CrazyGoat\Forex\WebSocket\Database\Query;

use CrazyGoat\Forex\ValueObject\Candle;
use CrazyGoat\Forex\ValueObject\Pair;
use CrazyGoat\Forex\ValueObject\Period;
use Doctrine\DBAL\Connection;

class LatestCandles
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param Pair   $pair
     * @param Period $period
     * @param int    $limit
     *
     * @return \Generator<Candle>
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public function __invoke(Pair $pair, Period $period, int $limit = 100): \Generator
    {
        $this->connection->getWrappedConnection()->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
        $query = $this->connection->executeQuery(
            'SELECT * FROM (
                SELECT * FROM candle_data 
                WHERE symbol=:symbol 
                AND period=:period 

                ORDER BY `time` DESC 
                LIMIT :limit
             ) as cd ORDER BY cd.time ASC',
            [
                'period' => $period->period(),
                'symbol' => $pair->symbol(),
                'limit' => $limit
            ],
            [
                'limit' => \PDO::PARAM_INT
            ]
        );

        while ($row = $query->fetchAssociative())  {
            $row['date'] = substr($row['time'],0, -3);
            yield Candle::fromArray($row);
        }
    }
}