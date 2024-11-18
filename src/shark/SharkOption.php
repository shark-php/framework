<?php

namespace Shark;

use Shark\Filesystem\Config\LocalFileSystemConfig;
use Shark\Logger\LoggerOption;

class SharkOption
{
    public LoggerOption $logger;
    public LocalFileSystemConfig $localFileSystemConfig;

    public function __construct(
        public string $root_path,
        public string $config_path = "",
        public string $storage_path = "/storage",
        public string $environment = "local",
        public bool $log_std = true,
        public bool $log_file = false,
        ?LoggerOption $logger = null,
        public array $config = []
    )
    {
        $this->logger = is_null($logger) ? new LoggerOption(std: $this->log_std,file: $this->log_file) : $logger;
        $this->localFileSystemConfig = new LocalFileSystemConfig($this->root_path,$this->storage_path);
    }
}