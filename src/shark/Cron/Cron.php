<?php
namespace Shark\Cron;

use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;

class Cron {
    private LoopInterface $loop;

    public function __construct(
        ?LoopInterface $loop = null,
    )
    {
        $this->loop = is_null($loop) ? Loop::get() : $loop;
    }

    /**
     * Run job
     *
     * @param callable $handler
     * @param int $duration
     * @return void
     */
    public function job(callable $handler, int $duration): void
    {
        $this->loop->addPeriodicTimer(60,$handler);
    }
}