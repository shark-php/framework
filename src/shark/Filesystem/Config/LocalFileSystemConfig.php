<?php

namespace Shark\Filesystem\Config;

class LocalFileSystemConfig
{
    public function __construct(
        public string $root_path,
        public string $storage_path = "/storage"
    )
    {
    }


    public function toArray():array
    {
        return [
            "root_path" => $this->root_path,
            "storage_path" => $this->storage_path
        ];
    }
}