<?php

namespace Shark\Filesystem\Config;

class FileSystemConfig
{
    public function __construct(
        public string $default = "local",
        public ?LocalFileSystemConfig $localFileSystemConfig = null
    )
    {
    }

    public function toArray( ) : array
    {
        return [
            "default" => $this->default,
            "local" => $this->localFileSystemConfig->toArray()
        ];
    }
}