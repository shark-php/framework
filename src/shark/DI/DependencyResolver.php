<?php
namespace Shark\DI;

use League\Container\Container;
use League\Container\DefinitionContainerInterface;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;
use ReflectionException;
use Shark\Exceptions\DependencyParamException;

class DependencyResolver
{

    private DefinitionContainerInterface $container;

    public function __construct(?DefinitionContainerInterface $container = null)
    {
       $this->container = is_null($container) ? $container : new Container();
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws DependencyParamException
     * @throws ReflectionException
     * @throws ContainerExceptionInterface
     */
    public function make($class, $params = [])
    {

        $reflectionClass    = new ReflectionClass($class);
        $constructor        = $reflectionClass->getConstructor();
        $tempParams         = [];

        if (!is_null($constructor))
        {
            $constructor_params = $constructor->getParameters();

            foreach ($constructor_params as $param)
            {
                if ($param->hasType())
                {
                    if ($param_class = $param->getClass())
                    {
                        if($this->container->has($param_class->name))
                        {
                            $temp_var = $this->container->get($param_class->name);
                        }
                        elseif (key_exists($param->name,$params))
                        {
                            $temp_var = $params[$param->name];
                        }
                        elseif($param_class->isInterface())
                        {
                            throw new DependencyParamException("Param " .$param->name ." is an interface without value");
                        }
                        elseif ($param->isDefaultValueAvailable())
                        {
                            $temp_var = $param->getDefaultValue();
                        }
                        else
                        {
                            $temp_var =  $this->make($param_class->name);
                        }
                    }
                    elseif($this->container->has($param->name))
                    {
                        $temp_var = $this->container->get($param_class->name);
                    }    
                    elseif (key_exists($param->name,$params))
                    {
                        $temp_var = $params[$param->name];
                    }
                    elseif ($param->isDefaultValueAvailable())
                    {
                        $temp_var = $param->getDefaultValue();
                    }
                    else
                    {
                        throw new DependencyParamException("Class param " .$param->name ." doesn't have default value");
                    }
                
                }
                elseif (key_exists($param->name,$params))
                {
                    $temp_var = $params[$param->name];
                }
                elseif ($param->isDefaultValueAvailable())
                {
                    $temp_var = $param->getDefaultValue();
                }
                else
                {
                    throw new DependencyParamException("Class param " .$param->name ." doesn't have default value");
                }
                $tempParams[$param->getPosition()] = $temp_var;
            }
        }
        return new $class(...$tempParams);
        
    }

    /**
     * @param string $key
     * @param mixed $params
     * @return void
     */
    public function add(string $key, mixed $params = null): void
    {
        $this->container->add($key, $params);
    }

    /**
     * @param string $key
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function get(string $key): mixed
    {
        return $this->container->get($key);
    }

    /**
     * @param AbstractServiceProvider|BootableServiceProviderInterface $provider
     * @return void
     */
    public function addServiceProvider(AbstractServiceProvider|BootableServiceProviderInterface $provider) :void
    {
        $this->container->addServiceProvider($provider);
    }
}
