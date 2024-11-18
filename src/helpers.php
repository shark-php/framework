<?php


use Psr\Log\LoggerInterface;

if (!function_exists("env"))
{
    /**
     * Get Env
     *
     * @param string $key
     * @param $default
     * @return mixed
     */
    function env(string $key, $default = null): mixed
    {
        return $_ENV[$key]??$default;
    }
}

if(!function_exists("head")){
    /**
     * @param array $arr
     * @return mixed
     */
    function head(array $arr): mixed {
        return reset($arr);
    }
}

if(!function_exists("tail")){
    /**
     * @param array $arr
     * @return array
     */
    function tail(array $arr): array
    {
        return array_slice($arr, 1);
    }
}

if(!function_exists("config")){
    /**
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    function config(string $key, mixed $default = null): mixed
    {
        return \Shark\Shark::getShark()->getConfig(key: $key,default: $default);
    }
}


if(!function_exists("logger")){
    /**
     * Get logger instance
     *
     * @return LoggerInterface
     */
    function logger(): LoggerInterface
    {
        return \Shark\Shark::getShark()->logger();
    }
}



if (!function_exists("response")){
    function response() : \React\Http\Message\Response
    {
        return new \React\Http\Message\Response();
    }
}

