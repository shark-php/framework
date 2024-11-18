<?php


namespace Shark\Database\Interfaces;


interface HandlerInterface
{
    public function getDriver() : DriverInterface;
}