<?php

namespace Shark\Database\Config;

class DatabaseConfig
{
    public function __construct(
        public string $default = "mysql",
        public ?MysqlConfig $mysql = null
    )
    {
    }

    public function toArray() : array
    {
        return [
            "default" => $this->default,
            "mysql" => $this->mysql->toArray()
        ];
    }
}