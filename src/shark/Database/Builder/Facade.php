<?php

namespace Shark\Database\Builder;

use NilPortugues\Sql\QueryBuilder\Builder\GenericBuilder;


/**
 * @method static Builder table(string $table)
 *
 * @see Builder
 */

class Facade
{
    public static function __callStatic($name, $arguments)
    {
        $builder = new Builder(new GenericBuilder());

        if (method_exists($builder,$name))
        {
            return $builder->{$name}(...$arguments);
        }

        throw new \Exception("Method not exists in Builder");
    }

}