<?php
namespace Shark\Database\Drivers\Mysql;

use Shark\Database\Interfaces\DriverInterface;
use React\MySQL\ConnectionInterface;
use React\Promise\PromiseInterface;
use function React\Promise\reject;

class MysqlDriver implements DriverInterface
{
    /**
     * @var ConnectionInterface
     */
    private ConnectionInterface $instance;

    public function __construct(ConnectionInterface $connection)
    {
        $this->instance = $connection;
    }

    public function exec($query, array $params = []): PromiseInterface
    {
        $query = preg_replace("/:\w+\d+/" ,"?",$query);

        logger()->debug("[MysqlDriver] sql=\"" . $query . "\"",[
            "params" => $params
        ]);

        foreach ($params as $key => $param)
        {
            if ($param == "NULL")
                $params[$key] = null;
        }
        return $this->instance->query($query,array_values($params))->catch(function (\Throwable $e) {
            logger()->debug("Error during run query : " . $e->getMessage(),[
                "exception" => $e
            ]);

            return reject($e);
        });
    }

}