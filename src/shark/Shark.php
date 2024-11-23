<?php

namespace Shark;

use Exception;
use League\Container\Container;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use ReflectionException;
use Shark\Config\Config;
use Shark\Cron\Cron;
use Shark\Cron\JobInterface;
use Shark\Database\Config\DatabaseConfig;
use Shark\Database\Config\MysqlConfig;
use Shark\Database\Exceptions\UnknownDatabaseDriverException;
use Shark\Database\Interfaces\DriverInterface;
use Shark\DI\DependencyResolver;
use Shark\Exceptions\DependencyParamException;
use Shark\Filesystem\Config\FileSystemConfig;
use Shark\Filesystem\Config\LocalFileSystemConfig;
use Shark\Filesystem\Contracts\FilesystemInterface;
use Shark\Filesystem\Exceptions\UnknownDriverException;
use Shark\Filesystem\FileSystemFactory;
use Shark\Http\Http;
use Shark\Http\HttpConfig;
use Shark\Logger\Factory;
use Shark\Logger\LoggerOption;

class Shark
{
    const CONFIG_KEY = "_config_key";

    const HTTP_CONFIG_KEY = "http_server";

    const DATABASE_CONFIG_KEY = "database";

    const FILESYSTEM_CONFIG_KEY = "filesystem";

    const ROOT_PATH_KEY = "_root_path_key";

    private static ?self $instance = null;

    private LoopInterface $loop;

    protected DependencyResolver $resolver;

    private ?Http $httpServer = null;

    private LoggerInterface $logger;

    private ?Cron $cron;

    /**
     * @param string $rootPath
     */
    private function __construct(string $rootPath = __DIR__)
    {
        $this->resolver = new DependencyResolver(new Container());
        $this->add(self::ROOT_PATH_KEY, $rootPath);

        $this->loop = Loop::get();
    }

    /**
     * Get instance of Shark with singleton
     *
     * @return Shark
     * @throws UnknownDriverException
     */
    public static function getShark(): self
    {
        if (self::$instance)
            return self::$instance;

        return self::createShark();
    }

    /**
     * Create instance of Shark
     *
     * @param SharkOption|null $options
     *
     * @return self
     * @throws UnknownDriverException
     */
    public static function createShark(?SharkOption $options = null) : self
    {
        if (!$options)
            $options = new SharkOption(__DIR__);

        $shark = new self($options->root_path);
        $config_path = $options->config_path;
        if ($config_path != "") {
            $config_path = $shark->getRootPath() . $config_path;
        }

        $shark->resolveConfig($config_path, $options->environment, $options->config);
        $shark->resolveFilesystem($options->storage_path);
        $shark->resolveLogger($options->logger);

        return self::$instance = $shark;
    }

    /**
     * Add to container
     *
     * @param $key
     * @param $value
     *
     * @return void
     */
    public function add($key, $value): void
    {
        $this->resolver->add($key,$value);
    }

    /**
     * Get from container
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function get(string $key): mixed
    {
        return $this->resolver->get($key);
    }

    /**
     * Register service provider
     *
     * @param AbstractServiceProvider|BootableServiceProviderInterface $serviceProvider
     *
     * @return void
     */
    public function registerServiceProvider(AbstractServiceProvider|BootableServiceProviderInterface $serviceProvider): void
    {
        $this->resolver->addServiceProvider($serviceProvider);
    }

    /**
     * Make instance of a class or other types which exists in container or not by resolving dependencies.
     *
     * @param $class
     * @param array $params
     *
     * @return mixed
     *
     * @throws ContainerExceptionInterface
     * @throws DependencyParamException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function make($class, array $params = []): mixed
    {
        return $this->resolver->make($class,$params);
    }


    /**
     * Get Logger instance
     *
     * @return LoggerInterface
     */
    public function logger() : LoggerInterface
    {
        return $this->logger;
    }

    /**
     * Get config
     *
     * @param string $key
     * @param mixed|null $default
     *
     * @return mixed
     */
    public function getConfig(string $key,mixed $default = null): mixed
    {
        try {
            /**
             * @var Config $config
             */
            $config = $this->get(self::CONFIG_KEY);
            return $config->get($key,$default);
        } catch (ContainerExceptionInterface|NotFoundExceptionInterface $e) {
            return null;
        }
    }

    /**
     * Get Filesystem
     *
     * @param string $driver
     * @return FilesystemInterface|null
     */
    public function fileSystem(string $driver = "") : ?FilesystemInterface
    {
        if ($driver == ""){
            $config = $this->getConfig(self::FILESYSTEM_CONFIG_KEY,[]);
            if (count($config)){
                $driver = $config["default"];
            }
        }

        try {
            /**
             * @return  FilesystemInterface
             */
            return $this->get($this->getFilesystemInstanceKey($driver));
        } catch (ContainerExceptionInterface|NotFoundExceptionInterface $e) {
            return null;
        }
    }

    /**
     * Get config
     *
     * @param string $key
     * @param mixed|null $value
     *
     * @return mixed
     */
    public function setConfig(string $key,mixed $value = null): mixed
    {
        try {
            /**
             * @var Config $config
             */
            $config = $this->get(self::CONFIG_KEY);
            return $config->set($key,$value);
        } catch (ContainerExceptionInterface|NotFoundExceptionInterface $e) {
            return null;
        }
    }



    /**
     * Get project root path
     *
     * @return string
     */
    public function getRootPath() : string
    {
        try {
            return $this->get(self::ROOT_PATH_KEY);
        } catch (ContainerExceptionInterface|NotFoundExceptionInterface $e) {
            return "";
        }
    }


    /**
     * Create new Http Server
     *
     * @param HttpConfig|null $config
     * @return Http
     */
    public function createHttpServer(?HttpConfig $config = null) : Http
    {
        if ($config && count($config->toArray())){
            $this->setConfig(self::HTTP_CONFIG_KEY,$config->toArray());
        } else {
            $tmp = $this->getConfig(self::HTTP_CONFIG_KEY,[]);
            $config = new HttpConfig();
            if (key_exists("url",$tmp)){
                $config->url = $tmp["url"];
            }
            if (key_exists("socket",$tmp)){
                $config->socket = $tmp["socket"];
            }
            if (key_exists("port",$tmp)){
                $config->port = $tmp["port"];
            }
            if (key_exists("buffer_size",$tmp)){
                $config->buffer_size = $tmp["buffer_size"];
            }
            if (key_exists("upload_max_file_size",$tmp)){
                $config->upload_max_file_size = $tmp["upload_max_file_size"];
            }
            if (key_exists("upload_max_file_count",$tmp)){
                $config->upload_max_file_count = $tmp["upload_max_file_count"];
            }
        }

        $server = new Http(
            $this->resolver,
            $this->loop,
            $config
        );

        if (!$this->httpServer){
            $this->httpServer = $server;
        }

        return $server;
    }

    /**
     * @throws UnknownDatabaseDriverException
     */
    public function getDatabaseDriver(string|DatabaseConfig $config = ""): DriverInterface
    {
        $createDriver = "";
        $tmp = $this->getConfig(self::DATABASE_CONFIG_KEY,[]);
        if (is_string($config)) {
            if (!count($tmp))
                throw new Exception("config is not loaded");

            if ($config != "" && !key_exists($config,$tmp)){
                throw new Exception("driver is not exists #{$config}");
            }

            $createDriver = $config;
            $config = new DatabaseConfig(
                default: $tmp["default"],
                mysql: key_exists("mysql",$tmp) ? new MysqlConfig(
                    database: $tmp["mysql"]["database"],
                    port: $tmp["mysql"]["port"],
                    username: $tmp["mysql"]["username"],
                    password: $tmp["mysql"]["password"],
                    host: $tmp["mysql"]["host"],
                ) : null
            );
        }

        if ($createDriver == "")
            $createDriver = $config->default;


        try {
            /**
             * @var DriverInterface $exists
             */
            $exists = $this->get($this->getDatabaseInstanceKey($createDriver));

            return $exists;
        } catch (ContainerExceptionInterface $e) {}

        $driver = (new Database\Factory($config,$this->loop))->createDriver($createDriver);
        $this->add($this->getDatabaseInstanceKey($createDriver), $driver);
        $this->add(DriverInterface::class, $driver);

        if (!count($tmp))
            $this->add(self::DATABASE_CONFIG_KEY,$config);

        return $driver;
    }

    /**
     * Add Jobs
     *
     * @param array<JobInterface> $jobs
     * @return void
     */
    public function addJobs(array $jobs): void
    {
        $this->resolveCron();
        foreach ($jobs as $job) {
            $this->cron->job(fn() => $job->handle(), $job->getDurationSeconds());
        }
    }

    /**
     * Add callable job
     *
     * @param callable $callable
     * @param int $duration
     * @return void
     */
    public function addJob(callable $callable, int $duration): void
    {
        $this->resolveCron();
        $this->cron->job($callable,$duration);
    }

    /**
     * Run application
     *
     * @return void
     */
    public function run(): void
    {
        $anyRunService = $this->runHttpServer();

        if (!$anyRunService)
            $this->logger()->warning("There is no runnable service");

        $this->loop->run();
    }

    /**
     * Resolve Cron jobs
     *
     * @return void
     */
    private function resolveCron(): void
    {
        if (is_null($this->cron))
            $this->cron = new Cron($this->loop);
    }

    /**
     * Resolve config folder
     *
     * @param string $config_path
     * @param string $env
     * @param array $configs
     *
     * @return void
     */
    private function resolveConfig(string $config_path = "",string $env = "local", array $configs = []): void
    {
        $config = new Config();

        if ($config_path != "") {
            $config->loadConfigurationFiles($config_path, $env);
        }

        if (!count($config->all()))
            $config->set($configs);

        $this->add(self::CONFIG_KEY,$config);
    }

    /**
     * Make Filesystem instance
     *
     * @param string $storage_path
     * @return void
     * @throws UnknownDriverException
     */
    private function resolveFilesystem(string $storage_path) : void
    {
        $config = $this->getConfig(self::FILESYSTEM_CONFIG_KEY,new FileSystemConfig(
            localFileSystemConfig: new LocalFileSystemConfig(
                $this->getRootPath(),
                $storage_path
            )
        ));

        if (is_array($config)) {
            $config = new FileSystemConfig(
                default: $config["default"],
                localFileSystemConfig: key_exists("local", $config) ? new LocalFileSystemConfig(
                    root_path:  $config["local"]["root_path"],
                    storage_path:  $config["local"]["storage_path"]
                ):  new LocalFileSystemConfig(
                    root_path:  $this->getRootPath(),
                    storage_path:  $storage_path,
                ),
            );
        }

        $factory = new FileSystemFactory($config);
        $local = $factory->create("local");
        $this->add($this->getFilesystemInstanceKey("local"),$local);
        $this->add(FilesystemInterface::class,$local);
        if ($config->default != "local"){
            $local = $factory->create($config->default);
            $this->add($this->getFilesystemInstanceKey($config->default),$local);
        }

        $this->setConfig(self::FILESYSTEM_CONFIG_KEY,$config->toArray());
    }

    /**
     * Resolve logger
     *
     * @param LoggerOption $loggerOption
     * @return void
     */
    private function resolveLogger(LoggerOption $loggerOption) : void
    {
        $this->logger = Factory::create(
            $this->getRootPath() . $loggerOption->path,
            $this->getConfig("app_name","Shark"),
            $loggerOption->level,
            $loggerOption->std,
            $loggerOption->file
        );
    }

    /**
     * Run exists http server or create server then run
     *
     * @return bool
     */
    private function runHttpServer(): bool
    {
        if (!$this->httpServer && $this->getConfig(self::HTTP_CONFIG_KEY)) {
            $this->createHttpServer();
        }

        if ($this->httpServer) {
            $this->httpServer->listen();
            return true;
        }

        return false;
    }

    /**
     * Get database instance prefix
     *
     * @param string $driver
     * @return string
     */
    private function getDatabaseInstanceKey(string $driver) :string
    {
        return "_database_instance." . $driver;
    }

    /**
     * Get filesystem instance prefix
     *
     * @param string $driver
     * @return string
     */
    private function getFilesystemInstanceKey(string $driver) :string
    {
        return "_filesystem_instance." . $driver;
    }
}