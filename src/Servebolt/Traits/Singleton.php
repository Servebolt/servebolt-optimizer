<?php

namespace Servebolt\Optimizer\Traits;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

trait Singleton
{
    /**
     * Singleton instances accessible by array key
     */
    protected static $instance;

    /**
     * Destroy singleton instance.
     */
    public static function destroyInstance(): void
    {
        self::$instance = null;
    }

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
