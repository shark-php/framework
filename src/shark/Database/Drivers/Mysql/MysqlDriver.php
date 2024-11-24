<?php
namespace Shark\Database\Drivers\Mysql;

use React\Mysql\MysqlClient;
use Shark\Database\Interfaces\DriverInterface;
use React\Promise\PromiseInterface;
use function React\Promise\reject;

class MysqlDriver implements DriverInterface
{
    /**
     * @var MysqlClient
     */
    private MysqlClient $instance;

    public function __construct(MysqlClient $connection)
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