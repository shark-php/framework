<?php


namespace Shark\Database\Drivers\Mysql;


use React\EventLoop\LoopInterface;
use React\Mysql\MysqlClient;
use Shark\Database\Interfaces\DriverInterface;
use Shark\Database\Interfaces\HandlerInterface;
use function LaravelIdea\throw_if;
use function React\Async\await;

class MysqlFactory implements HandlerInterface
{
    private string $username;
    private string $password;
    private string $host;
    private string $port;
    private string $database;
    private ?MysqlClient $connection = null;

    public function __construct(
        private readonly LoopInterface $loop,
        string                         $database,
        string                         $port,
        string                         $username,
        string                         $password,
        string                         $host
    )
    {
        $this->database     = $database;
        $this->port         = $port;
        $this->username     = $username;
        $this->password     = $password;
        $this->host         = $host;
    }

    public function getDriver(): DriverInterface
    {
        if (!$this->connection){
            $this->connection = new  MysqlClient(uri: $this->getConfig(), loop: $this->loop);
        }
        try {
            await($this->connection->ping());
        } catch (\Throwable $e) {
            throw $e;
        }

        return new MysqlDriver( $this->connection );
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