<?php
namespace Shark\Http\Request;


use Psr\Http\Message\ServerRequestInterface;

class Request implements ServerRequestInterface
{
    use ServerRequestImplementsTrait;

    public ServerRequestInterface $request;
    private mixed $auth = null;
    
    public function __construct(ServerRequestInterface $request) {
        $this->request = $request;
    }

    public function __get($name)
    {
        if (property_exists($this , $name)){
            return $this->{$name};
        }

        $parsed_body = $this->request->getParsedBody();
        if (isset($parsed_body[$name]))
        {
            return $parsed_body[$name];
        }

        $query_params = $this->getQueryParams();
        if (isset($query_params[$name]))
        {
            return $query_params[$name];
        }

        return null;
    }

    public function input($input,$default = null)
    {
        return $this->{$input} ?? $default;
    }

    public function all()
    {
        return $this->request->getParsedBody();
    }

    public function addAuth($auth): void
    {
        $this->auth = $auth;
    }

    public function getAuth(): mixed
    {
        return $this->auth;
    }
}