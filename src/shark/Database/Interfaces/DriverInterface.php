<?php

namespace Shark\Database\Interfaces;


use React\Promise\PromiseInterface;

interface DriverInterface
{
    public function exec($query, array $params = []) : PromiseInterface;
}