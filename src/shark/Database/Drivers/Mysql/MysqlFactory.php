<?php


namespace Shark\Database\Drivers\Mysql;



use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use React\MySQL\Factory as Mysql;
use Shark\Database\Interfaces\DriverInterface;
use Shark\Database\Interfaces\HandlerInterface;

class MysqlFactory implements HandlerInterface
{
    private string $username;
    private string $password;
    private string $host;
    private string $port;
    private string $database;
    private Mysql $connection;

    public function __construct(
        private readonly LoopInterface $loop,
        string                         $database,
        string                         $port,
        string                         $username,
        string                         $password,
        string                         $host
    )
    {
        $mysql = new Mysql($this->loop);

        $this->database     = $database;
        $this->port         = $port;
        $this->username     = $username;
        $this->password     = $password;
        $this->host         = $host;
        $this->connection   = $mysql;
    }

    public function getDriver(): DriverInterface
    {
        return new MysqlDriver( $this->connection->createLazyConnection( $this->getConfig() ) );
    }

    private function getConfig(): string
    {
        return  $this->username .
            ":" .
            $this->password .
            "@".
            $this->host .
            ":" .
            $this->port .
            "/".
            $this->database;
    }

}