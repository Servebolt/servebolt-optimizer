<?php

namespace Servebolt\Optimizer\Traits;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Trait EventToggler
 * @package Servebolt\Optimizer\Traits
 */
trait EventToggler {
    public static function reload(): void
    {
        self::off();
        self::on();
    }

    public static function on(): void
    {
        $instance = self::getInstance();
        $instance->registerEvents();
    }

    public static function off(): void
    {
        $instance = self::getInstance();
        $instance->deregisterEvents();
    }
}
