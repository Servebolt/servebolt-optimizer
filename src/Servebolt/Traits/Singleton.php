<?php

namespace Servebolt\Optimizer\Traits;

trait Singleton
{
    /**
     * Singleton instances accessible by array key
     */
    protected static $instance;

    /**
     * @return mixed
     */
    public static function getInstance()
    {
        if (!self::$instance) {
            $args = func_get_args();
            $static = get_called_class();
            if (count($args) > 0 && method_exists($static, '__construct')) {
                static::$instance = new $static(...$args);
            } else {
                static::$instance = new $static;
            }
        }
        return self::$instance;
    }
}
