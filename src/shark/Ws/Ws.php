<?php

namespace Shark\Ws;

use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use React\Socket\SocketServer;

class Ws
{
    private SocketServer $server;

    private LoopInterface $loop;

    private WsConfig $config;


    public function __construct(
        ?LoopInterface $loop = null,
        ?WsConfig $config = null
    )
    {
        $this->loop = is_null($loop) ? Loop::get() : $loop;
        $this->config = is_null($config) ? new WsConfig() : $config;
        $this->server = new SocketServer(
            uri: $this->config->url,
            context: $this->config->context->toArray(),
            loop: $this->loop
        );
    }

    public function on(string $event,callable $handler) : void
    {
        $this->server->on($event,$handler);
    }

    public function emit(string $event, array  $arg): void
    {
        $this->server->emit($event,$arg);
    }
}