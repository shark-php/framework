<?php

namespace Shark\Cron;

interface JobInterface
{
    public function getDurationSeconds() : int;

    public function handle();
}