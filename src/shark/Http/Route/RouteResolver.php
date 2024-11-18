<?php
namespace Shark\Http\Route;

use FastRoute\Dispatcher;
use Shark\DI\DependencyResolver;
use Shark\Http\Exceptions\MethodNotAllowedException;
use Shark\Http\Exceptions\NotFoundException;
use Shark\Http\Route\Dispatcher\RouteDispatcher;
use Exception;
use LogicException;
use Psr\Http\Message\ServerRequestInterface;


final class RouteResolver {

    private RouteDispatcher $dispatch;
    private DependencyResolver $resolver;

    private array $middlewares_alias = [];

    private array $middlewares_pool = [];
    private array $controllers_pool = [];
    public function __construct(
        RouteDispatcher $dispatcher,
        DependencyResolver $diResolver,
        array $middlewares_alias = []
    )
    {
        $this->dispatch     = $dispatcher;
        $this->resolver     = $diResolver;
        $this->middlewares_alias = $middlewares_alias;
    }

    public function __invoke(ServerRequestInterface $request)
    {
        $route = $this->dispatch->dispatch(
            $request->getMethod(), $request->getUri()->getPath()
        );

        switch ($route[0])
        {
            case Dispatcher::NOT_FOUND:
                throw new NotFoundException("Route Not Found",404);
            case Dispatcher::METHOD_NOT_ALLOWED:
                $method     = $route[1][0];
                $req_method =  $request->getMethod();
                throw new MethodNotAllowedException("Method '$req_method' does not supported. Supported method is '$method' ",405);
            case Dispatcher::FOUND:
                $params     = array_values($route[2]);
                $controller = $this->findController($route[1]);
                $response   = $this->execController($request, $controller, $params, $route[3]??[]);

                return $response;
        }

        throw new LogicException("wrong");
    }

    private function findController($classes) : \Closure|array
    {
        if (is_array($classes))
        {
            return ["controller" => $classes[0], "action" => $classes[1]];
        }
        else if ($classes instanceof \Closure)
        {
            return $classes;
        }
        elseif(is_string($classes))
        {
            return ["invoke" => $classes];
        }

        return [];
    }

    private function execController($request, $controller , $params, $middlewares)
    {
        if (is_array($controller) )
        {
            if(isset($controller["controller"]))
            {
                $action     = $controller["action"];
               
                if(!key_exists($controller["controller"],$this->controllers_pool))
                {
                    $controller = $this->resolver->make($name = $controller["controller"]);
                    $this->controllers_pool[$name] = $controller;
                }
                else
                {
                    $controller = $this->controllers_pool[$controller["controller"]];
                }

                if(count($middlewares))
                {
                    $response = $this->execMiddleware($request,$middlewares,$controller,$params,$action);
                }
                else
                    $response   = $controller->{$action}($request,...$params);
            }
            else if (isset($controller["invoke"]))
            {

                if(!key_exists($controller["invoke"],$this->controllers_pool))
                {
                    $controller = $this->resolver->make($name = $controller["invoke"]);
                    $this->controllers_pool[$name] = $controller;
                }
                else
                {
                    $controller = $this->controllers_pool[$controller["invoke"]];
                }
                
                if(count($middlewares))
                {
                    $response = $this->execMiddleware($request,$middlewares,$controller,$params);
                }
                else
                    $response   = $controller($request,...$params);
            }
        }
        else{
            if(count($middlewares))
            {
                $response = $this->execMiddleware($request,$middlewares,$controller,$params);
            }
            else
                $response   = $controller($request,...$params);
        }

        return $response;
    }

    private function execMiddleware(ServerRequestInterface $request, $middlewares, $controller,$route_params ,$action = null)
    {
        if(!count($middlewares))
        {
            
            return function(ServerRequestInterface $request) use($controller,$action,$route_params){
                if(!is_null($action)){
                    return $controller->{$action}($request,...$route_params);
                }else{
                    return $controller($request,...$route_params);
                }
            };
        }

        $next       = head($middlewares);
        $middleware = explode(":",$next);
        $next       = $middleware[0];
        $params     = [];

        if(count($middleware) == 2)
        {
            $params = explode(",",$middleware[1]);
        }

        if(key_exists($next,$this->middlewares_pool))
        {
            $next = $this->middlewares_pool[$next];
        }
        else
        {
            $next = $this->resolver->make($this->findMiddlewareFromConfig($next));
            $this->middlewares_pool[$middleware[0]] = $next;
        }
        $tail = tail($middlewares);

        return $next($request,$this->execMiddleware($request,$tail,$controller,$route_params ,$action),...$params);
    }

    private function findMiddlewareFromConfig($middleware){
        if(key_exists($middleware,$this->middlewares_alias)){
            return $this->middlewares_alias[$middleware];
        }
        throw new Exception("Middleware called '$middleware' not found!");
    }
}