<?php

namespace Shark\Logger;

class LoggerOption
{
    public function __construct(
        public string $path = "/storage/logs/app.log",
        public string $level = Factory::Info,
        public bool  $std = true,
        public bool $file = false
    )
    {
    }
}