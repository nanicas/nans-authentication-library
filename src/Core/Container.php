<?php

namespace Nanicas\Auth\Core;

use Psr\Container\ContainerInterface;
use Closure;
use Exception;
use ReflectionClass;

class Container implements ContainerInterface
{
    protected $bindings = [];
    protected $forceBind = false;

    public function bind(string $id, Closure|string $concrete)
    {
        $this->bindings[$id] = $concrete;
    }

    public function setForceBind(bool $value)
    {
        $this->forceBind = $value;
    }

    public function isForcedBind()
    {
        return $this->forceBind;
    }

    public function get(string $id)
    {
        if ($this->has($id)) {
            $concrete = $this->bindings[$id];
            if (is_callable($concrete)) {
                return $concrete();
            }
            return $this->injectDependencies($concrete);
        }

        if ($this->isForcedBind()) {
            throw new Exception("{$id} is not bound in the container.");
        }

        return $this->injectDependencies($id);
    }

    public function has(string $id): bool
    {
        return isset($this->bindings[$id]);
    }

    protected function injectDependencies($concrete)
    {
        $reflector = new ReflectionClass($concrete);
        if (!$reflector->isInstantiable()) {
            throw new Exception("Concrete {$concrete} is not instantiable.");
        }

        $constructor = $reflector->getConstructor();
        $parameters = $constructor ? $constructor->getParameters() : [];

        $dependencies = [];
        foreach ($parameters as $parameter) {
            $dependency = $parameter->getClass();
            if (is_object($dependency)) {
                $dependencies[] = $this->get($dependency->name);
            } else {
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                    throw new Exception("Parameter {$parameter->getName()} is not available.");
                }
            }
        }

        return (empty($dependencies)) 
            ? $reflector->newInstance() 
            : $reflector->newInstanceArgs($dependencies);
    }
}
