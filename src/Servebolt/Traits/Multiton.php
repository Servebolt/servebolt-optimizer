<?php

namespace Servebolt\Optimizer\Traits;

trait Multiton
{
    /**
     * Singleton instances accessible by array key
     */
    protected static $instances = [];

    /**
     * @param null|string $name
     * @return mixed
     */
    public static function getInstance($name = null)
    {
        $args = array_slice(func_get_args(), 1);
        $name = $name ?: 'default';
        $static = get_called_class();
        $key = sprintf('%s::%s', $static, $name);
        if (!array_key_exists($key, static::$instances)) {
            if (count($args) > 0 && method_exists($static, '__construct')) {
                static::$instances[$key] = new $static($args);
            } else {
                static::$instances[$key] = new $static;
            }
        }
        return static::$instances[$key];
    }
}
