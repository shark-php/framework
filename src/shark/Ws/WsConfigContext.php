<?php

namespace Shark\Ws;

class WsConfigContext
{
    public function __construct(
        public array $tcp = [],
        public array $tls = [],
        public array $unix = [],
    )
    {
    }

    public function toArray() : array
    {
        return [
            "tcp" => $this->tcp,
            "tls" => $this->tls,
            "unix" => $this->unix,
        ];
    }
}