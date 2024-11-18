<?php

namespace Shark\Database;



use React\EventLoop\LoopInterface;
use Shark\Database\Config\DatabaseConfig;
use Shark\Database\Drivers\Mysql\MysqlFactory;
use Shark\Database\Exceptions\UnknownDatabaseDriverException;
use Shark\Database\Interfaces\DriverInterface;

class Factory {

    public function __construct(
        private readonly DatabaseConfig $config,
        private readonly LoopInterface  $loop,
    )
    {
    }

    public function createDriver(string $driver): DriverInterface
    {
        $db = null;

        if (str_contains($driver, "mysql"))
        {
            $driver = new MysqlFactory(
                $this->loop,
                $this->config->mysql->database,
                $this->config->mysql->port,
                $this->config->mysql->username,
                $this->config->mysql->password,
                $this->config->mysql->host,
            );
            $db = $driver->getDriver();
        }
        
        if(!$db)
            throw new UnknownDatabaseDriverException("Unknown Database driver");

        return $db;
    }
}