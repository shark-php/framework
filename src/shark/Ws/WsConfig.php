<?php

namespace Shark\Ws;

class WsConfig
{

    public WsConfigContext $context;

    public function __construct(
        public string $url = "127.0.0.1:3001",
        ?WsConfigContext $context = null,
    )
    {
        $this->context = is_null($context) ? new WsConfigContext() : $context;
    }
}