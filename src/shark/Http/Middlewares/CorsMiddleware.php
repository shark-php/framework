<?php
namespace Shark\Http\Middlewares;

use React\Http\Message\Response;

class CorsMiddleware
{
    public function __invoke($serverRequest, callable $next)
    {
        if (preg_match('/options/i',$serverRequest->getMethod()))
        {
            return new Response(Response::STATUS_NO_CONTENT);
        }

        $response = $next($serverRequest);
        if ($response instanceof \React\Promise\PromiseInterface)
            return $response->then(function ($response){
                return $this->addCorsHeaders($response);
            });

        return $this->addCorsHeaders($response);
    }

    protected function addCorsHeaders($response)
    {
        $response =  $response->withHeader("Access-Control-Allow-Origin", "*");
        $response =  $response->withHeader("Access-Control-Allow-Credentials", "true");
        $response =  $response->withHeader("Access-Control-Allow-Methods", "GET, PUT, POST, DELETE, OPTIONS, HEAD");
        $response =  $response->withHeader("Access-Control-Allow-Headers", "Origin, Content-Type, X-Auth-Token , Authorization");
        return $response;
    }

}