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
     * @return mixed
     * @throws ReflectionException
     */
    public static function getInstance() {
        if (!self::$instance) {
            $args = func_get_args();
            $static = get_called_class();
            $ref = new \ReflectionClass($static);
            $ctor = is_callable($ref, '__construct');
            static::$instance = (!!count($args) && $ctor)
                ? $ref->newInstanceArgs($args)
                : $ref->newInstanceWithoutConstructor();
        }
        return self::$instance;
    }
}
