<?php

namespace TitanFields\Core\DependencyInjection;

use Exception;
use ReflectionClass;

class Container
{
    /**
     * @var array
     */
    private array $services = [];

    /**
     * @var array
     */
    private array $instances = [];

    /**
     * Register a service.
     *
     * @param string $id
     * @param string|callable|object $concrete
     */
    public function bind(string $id, $concrete): void
    {
        $this->services[$id] = $concrete;
    }

    /**
     * Register a singleton service.
     *
     * @param string $id
     * @param string|callable|object $concrete
     */
    public function singleton(string $id, $concrete): void
    {
        $this->bind($id, function () use ($id, $concrete) {
            if (!isset($this->instances[$id])) {
                $this->instances[$id] = $this->resolve($concrete);
            }
            return $this->instances[$id];
        });
    }

    /**
     * Get a service from the container.
     *
     * @param string $id
     * @return mixed
     * @throws Exception
     */
    public function get(string $id): mixed
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        if (!isset($this->services[$id])) {
            throw new Exception("Service not found: {$id}");
        }

        return $this->resolve($this->services[$id]);
    }

    /**
     * Determine if a service is registered.
     *
     * @param string $id
     * @return bool
     */
    public function has(string $id): bool
    {
        return isset($this->services[$id]) || isset($this->instances[$id]);
    }

    /**
     * Resolve the concrete instance.
     *
     * @param mixed $concrete
     * @return mixed
     * @throws Exception
     */
    private function resolve($concrete): mixed
    {
        if (is_callable($concrete)) {
            return $concrete($this);
        }

        if (is_string($concrete) && class_exists($concrete)) {
            $reflector = new ReflectionClass($concrete);

            if (!$reflector->isInstantiable()) {
                throw new Exception("Class {$concrete} is not instantiable.");
            }

            $constructor = $reflector->getConstructor();

            if (is_null($constructor)) {
                return new $concrete();
            }

            $parameters = $constructor->getParameters();
            $dependencies = [];

            foreach ($parameters as $parameter) {
                $type = $parameter->getType();
                if (!$type || $type->isBuiltin()) {
                    if ($parameter->isDefaultValueAvailable()) {
                        $dependencies[] = $parameter->getDefaultValue();
                    } else {
                        throw new Exception("Cannot resolve parameter {$parameter->name} in {$concrete}");
                    }
                } else {
                    $dependencies[] = $this->get($type->getName());
                }
            }

            return $reflector->newInstanceArgs($dependencies);
        }

        return $concrete;
    }
}
