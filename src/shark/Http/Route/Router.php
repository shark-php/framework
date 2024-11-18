<?php

namespace Shark\Http\Route;

use FastRoute\DataGenerator\GroupCountBased;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std;

class Router
{
    static private string $uri = "";

    private array $route_middlewares = [];
    private array $middlewares = [];

    private RouteCollector $routeCollector;


    public function __construct()
    {
        $this->routeCollector = new RouteCollector(new Std() ,new GroupCountBased());
    }

    public function get($path , $function, array $middleware = []): void
    {
        $this->makeRoute('get',$path , $function,$middleware);
    }


    public function post($path , $function, array $middleware = []): void
    {
        $this->makeRoute('post',$path , $function,$middleware);
    }

    public function put($path , $function, array $middleware = []): void
    {
        $this->makeRoute('put',$path , $function,$middleware);
    }


    public function patch($path , $function, array $middleware = []): void
    {
        $this->makeRoute('patch',$path , $function,$middleware);
    }


    public function delete($path , $function, array $middleware = []): void
    {
        $this->makeRoute('delete',$path , $function,$middleware);
    }


    public function group($prefix , callable $function, array $middleware = []): void
    {
        $previousGroupPrefix    = self::$uri;
        self::$uri              = $previousGroupPrefix . $this->uriSlashCheck($prefix);
        $prev_middlewares       = $this->middlewares;
        $this->middlewares      = array_merge($this->middlewares,$middleware);
        
        // routes inside group
        $function();

        $this->middlewares      = $prev_middlewares;
        self::$uri              = $previousGroupPrefix;
    }

    private function uriSlashCheck($path)
    {
        if (strlen($path) == 0 || $path == '/') {
            if (self::$uri == '')
                return '/';
            return '';
        }

        if (str_ends_with(self::$uri, '/') && str_starts_with($path, '/'))
        {
            $path =  substr($path , 1);
        }
        elseif (!str_ends_with(self::$uri, '/') && !str_starts_with($path, '/'))
        {
            $path =  '/' . $path;
        }


        if (str_ends_with($path, '/'))
            $path = substr($path,0,-1);

        return $path;
    }

    private function makeRoute($type,$path,$function , $middleware): void
    {
        $path               = self::$uri . $this->uriSlashCheck($path);

        $this->addMiddlewareToRoutes($type,$path,array_merge($this->middlewares,$middleware));

        $this->routeCollector->{$type}($path,$function);
    }

    private function addMiddlewareToRoutes($method,$path,$middleware): void
    {
        $method = strtoupper($method);

        if(isset($this->route_middlewares[$method]))
        {
            $this->route_middlewares[$method][$path] = array_merge($this->route_middlewares[$method][$path]??[],$middleware);
        }
        else
        {
            $this->route_middlewares[$method] = [$path => $middleware];
        }
    }

    public function getRoutesMiddleware(): array
    {
        return $this->route_middlewares;
    }

    public function getData(): array
    {
        return $this->routeCollector->getData();
    }

}