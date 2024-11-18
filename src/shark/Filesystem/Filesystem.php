<?php 
namespace Shark\Filesystem;

use Psr\Http\Message\UploadedFileInterface;
use React\Filesystem\AdapterInterface;
use React\Filesystem\Factory as ReactFilesystemFactory;
use React\Promise\PromiseInterface;
use Shark\Filesystem\Config\LocalFileSystemConfig;
use Shark\Filesystem\Contracts\FilesystemInterface;
use Shark\Filesystem\Entities\File;
use Shark\Filesystem\Exceptions\FileNotFoundException;
use Shark\Filesystem\Exceptions\UnknownException;
use function React\Promise\reject;
use function React\Promise\resolve;

class Filesystem implements FilesystemInterface {

    private AdapterInterface $filesystem;


    public function __construct(
        private readonly LocalFileSystemConfig $config
    )
    {
        $this->filesystem = ReactFilesystemFactory::create();
    }

    /**
     * put function
     *
     * @param UploadedFileInterface $file
     * @param string $dir
     * @param string|null $name
     * @return PromiseInterface
     */
    public function put(UploadedFileInterface $file, string $dir = "",?string $name = null) : PromiseInterface{
        $name       = $this->makeFileName($file,$name);
        $uploadPath = $this->config->root_path . '/' . $this->makeFilePath($dir);

        if (!is_dir($uploadPath)) 
            mkdir($uploadPath);
        

        $fullPath =  $uploadPath . $name;

        $content = (string)$file->getStream();
        file_put_contents($fullPath,$content);

        return resolve($name);    
    }

    /**
     * move function
     *
     * @param string $path
     * @param string $target
     * @return PromiseInterface
     */
    public function move(string $path, string $target): PromiseInterface{
        return rename($path, $target) ? resolve(true) : reject(new UnknownException("error during move file"));
    }

    /**
     * copy function
     *
     * @param string $path
     * @param string $target
     * @return PromiseInterface
     */
    public function copy( string $path, string $target):PromiseInterface{
        return copy($path, $target) ? resolve(true) : reject(new UnknownException("error during copy file"));
    }

    /**
     * delete function
     *
     * @param string $paths
     * @return PromiseInterface
     */
    public function delete(string $paths): PromiseInterface{
        $fullPath =  $this->getStorageFullPath() . $paths;

        if(file_exists($fullPath))
        {
            unlink($fullPath);
            return resolve(true);
        }
        
        return reject(new UnknownException("error during delete file"));
    }

    /**
     * get function
     *
     * @param string $path
     * @return PromiseInterface
     */
    public function get(string $path): PromiseInterface{
        $file = $this->filesystem->file( $this->getStorageFullPath() . $path);

        if(!file_exists($file->path() . $file->name()))
            return reject(new FileNotFoundException());

        $mimeType = mime_content_type($file->path() . $file->name());
        $contents = file_get_contents($file->path() . $file->name());

        return resolve(new File($contents,$mimeType));
    }


    protected function makeFilePath(string $name): string
    {
        return implode(
            '',
            [
                $this->config->storage_path,
                '/',
                $name,
            ]
        );
    }

    private function makeFileName(UploadedFileInterface $file,?string $name = null) :string
    {
        preg_match('/^.*\.(.+)$/', $file->getClientFilename(), $filenameParsed);
        if(!$name)
            $name = md5(uniqid(rand(), true));

        return implode(
            '',
            [
                $name,
                '.',
                $filenameParsed[1],
            ]
        );
    }

    private function getStorageFullPath() : string
    {
        return $this->config->root_path . $this->config->storage_path;
    }
}