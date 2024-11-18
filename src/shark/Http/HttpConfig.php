<?php

namespace Shark\Http;

class HttpConfig
{
    public function __construct(
        public string $url = "http://127.0.0.1/",
        public string $socket = "127.0.0.1",
        public string $port = "8080",
        public int $buffer_size = 8 * 1024 * 1024,
        public int $upload_max_file_size = 8 * 1024 * 1024,
        public int $upload_max_file_count = 1,
        public array $middlewares_alias = [],
        public array $config_middlewares = [],
    )
    {
    }


    public function toArray() : array
    {
        return [
            "url" => $this->url,
            "socket" => $this->socket,
            "port" => $this->port,
            "buffer_size" => $this->buffer_size,
            "upload_max_file_size" => $this->upload_max_file_size,
            "upload_max_file_count" => $this->upload_max_file_count,
            "middlewares_alias" => $this->middlewares_alias,
            "config_middlewares" => $this->config_middlewares,
        ];
    }
}