<?php

namespace Shark\Http;

use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use React\Http\HttpServer;
use React\Http\Middleware\RequestBodyBufferMiddleware;
use React\Http\Middleware\RequestBodyParserMiddleware;
use React\Http\Middleware\StreamingRequestMiddleware;
use React\Socket\SocketServer;
use Shark\DI\DependencyResolver;
use Shark\Http\Middlewares\SharkMiddleware;
use Shark\Http\Middlewares\JsonRequestDecoder;
use Shark\Http\Route\Dispatcher\RouteDispatcher;
use Shark\Http\Route\Router;
use Shark\Http\Route\RouteResolver;
use Shark\Traits\ConfigTrait;

class Http
{
    use ConfigTrait;

    private LoopInterface $loop;

    private array $globMiddlewares = [];

    private Router $router;

    private SocketServer $ws;

    /**
     * @param DependencyResolver $resolver
     * @param LoopInterface|null $loop
     * @param ?HttpConfig $configs
     */
    public function __construct(
        private readonly DependencyResolver $resolver,
        ?LoopInterface                      $loop = null,
        ?HttpConfig                         $configs = null,
    )
    {
        $this->loop = is_null($loop) ? Loop::get() : $loop;

        if (!$configs){
            $configs = new HttpConfig();
        }
        $this->setConfigs($configs->toArray());
        $this->router = new Router();
    }

    public function addGlobalMiddleware(mixed $middleware): void
    {
        $this->globMiddlewares[] = $middleware;
    }

    public function setRouter(Router $router): void
    {
        $this->router = $router;
    }

    public function getRouter() : Router
    {
        return $this->router;
    }

    public function listen(): void
    {
        $this->ws = new SocketServer(sprintf(
            "%s:%d",
            $this->getConfig("socket"),
            $this->getConfig("port")
        ));

        $chain = $this->globMiddlewares;

        $chain[] = new RouteResolver(
            new RouteDispatcher($this->router),
            $this->resolver,
            $this->getConfig("middlewares_alias",[])
        );

        $http =  new HttpServer(
            $this->loop,
            new StreamingRequestMiddleware(),
            new RequestBodyBufferMiddleware($this->getConfig("buffer_size")),
            new RequestBodyParserMiddleware($this->getConfig("upload_max_file_size"), $this->getConfig("upload_max_file_count")), // 8 MiB
            new SharkMiddleware($this->ws),
            ...$chain
        );

        $http->listen($this->ws);

        $this->ws->on("error" , fn(\Throwable $exception) => logger()->error($exception->getMessage(),[$exception]));
    }

}