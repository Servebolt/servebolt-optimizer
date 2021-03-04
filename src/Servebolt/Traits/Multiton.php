<?php

namespace Servebolt\Optimizer\Traits;

use ReflectionException;

trait Multiton
{
    /**
     * Singleton instances accessible by array key
     */
    protected static $instances = [];

    protected function __construct() { }
    protected function __clone() { }
    protected function __sleep() { }
    protected function __wakeup() { }

    /**
     * @param null|string $name
     * @return mixed
     * @throws ReflectionException
     */
    public static function getInstance($name = null)
    {
        $args = array_slice(func_get_args(), 1);
        $name = $name ?: 'default';
        $static = get_called_class();
        $key = sprintf('%s::%s', $static, $name);
        if (!array_key_exists($key, static::$instances)) {
            $ref = new \ReflectionClass($static);
            $ctor = is_callable($ref, '__construct');
            static::$instances[$key] = (!!count($args) && $ctor)
                ? $ref->newInstanceArgs($args)
                : $ref->newInstanceWithoutConstructor();
        }
        return static::$instances[$key];
    }
}
