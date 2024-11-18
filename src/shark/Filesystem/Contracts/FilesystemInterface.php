<?php 
namespace Shark\Filesystem\Contracts;

use Psr\Http\Message\UploadedFileInterface;
use React\Promise\PromiseInterface;

interface FilesystemInterface{

    /**
     * put function
     *
     * @param UploadedFileInterface $file
     * @param string $dir
     * @param string|null $name
     * @return PromiseInterface
     */
    public function put(UploadedFileInterface $file, string $dir = "",?string $name = null) : PromiseInterface;

    /**
     * move function
     *
     * @param string $path
     * @param string $target
     * @return PromiseInterface
     */
    public function move(string $path, string $target): PromiseInterface;

    /**
     * copy function
     *
     * @param string $path
     * @param string $target
     * @return PromiseInterface
     */
    public function copy(string $path, string $target):PromiseInterface;

    /**
     * delete function
     *
     * @param string $paths
     * @return PromiseInterface
     */
    public function delete(string $paths): PromiseInterface;

    /**
     * get function
     *
     * @param string $path
     * @return PromiseInterface
     */
    public function get(string $path): PromiseInterface;

}