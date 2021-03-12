<?php

namespace Servebolt\Optimizer\Traits;

use ReflectionException;

trait Singleton
{
    /**
     * Singleton instances accessible by array key
     */
    protected static $instance;

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
     * @return mixed
     * @throws ReflectionException
     */
    public static function getInstance()
    {
        if (!self::$instance) {
            $args = func_get_args();
            $static = get_called_class();
            $reflectionClass = new \ReflectionClass($static);
            $constructorIsCallable = self::hasPublicConstructor($static);
            if ($constructorIsCallable) {
                if (count($args) > 0) {
                    static::$instance = $reflectionClass->newInstanceArgs($args);
                } else {
                    static::$instance = $reflectionClass->newInstance();
                }
            } else {
                static::$instance = $reflectionClass->newInstanceWithoutConstructor();
            }
        }
        return self::$instance;
    }
}
