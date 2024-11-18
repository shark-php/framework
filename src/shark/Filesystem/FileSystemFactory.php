<?php
namespace Shark\Filesystem;

use Shark\Filesystem\Config\FileSystemConfig;
use Shark\Filesystem\Contracts\FilesystemInterface;
use Shark\Filesystem\Exceptions\UnknownDriverException;

class FileSystemFactory{


    public function __construct(
        private readonly FileSystemConfig $config,
    )
    {
    }

    /**
     * create function
     *
     * @param string $driver
     * @return FilesystemInterface
     * @throws UnknownDriverException
     */
    public function create(string $driver = ""): FilesystemInterface{
        if ($driver == "")
            $driver = $this->config->default;

        return match ($driver) {
            "local" => new FileSystem($this->config->localFileSystemConfig),
            "aws" => throw new \RuntimeException("not implemented"),
            default => throw new UnknownDriverException("Invalid driver type."),
        };
    }
}