<?php

namespace Shark\Database\Config;

class MysqlConfig
{
    public function __construct(
        public string $database,
        public string $port,
        public string $username,
        public string $password,
        public string $host,
    )
    {
    }

    public function toArray() : array
    {
        return [
            "database" => $this->database,
            "port" => $this->port,
            "username" => $this->username,
            "password" => $this->password,
            "host" => $this->host,
        ];
    }
}