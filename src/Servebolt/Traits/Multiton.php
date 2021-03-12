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
     * @param $class
     * @return bool
     */
    private static function hasPublicConstructor($class): bool
    {
        try {
            $constructMethod = new \ReflectionMethod($class, '__construct');
            if ($constructMethod->isPublic()) {
                return true;
            }
        } catch (ReflectionException $e) {}
        return false;
    }

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
            $reflectionClass = new \ReflectionClass($static);
            $constructorIsCallable = self::hasPublicConstructor($static);
            if ($constructorIsCallable) {
                if (count($args) > 0) {
                    static::$instances[$key] = $reflectionClass->newInstanceArgs($args);
                } else {
                    static::$instances[$key] = $reflectionClass->newInstance();
                }
            } else {
                static::$instances[$key] = $reflectionClass->newInstanceWithoutConstructor();
            }
        }
        return static::$instances[$key];
    }
}
