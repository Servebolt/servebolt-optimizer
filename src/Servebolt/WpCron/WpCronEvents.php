<?php

namespace Servebolt\Optimizer\WpCron;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Class WpCronEvents
 * @package Servebolt\Optimizer\WpCron
 */
class WpCronEvents
{
    /**
     * CronEvent constructor.
     */
    public function __construct()
    {
        $this->registerEvents();
    }

    private function registerEvents(): void
    {
        foreach(glob(__DIR__ . '/Events/*.php') as $file) {
            $class = '\\Servebolt\\Optimizer\\WpCron\\Events\\' . basename($file, '.php');
            if (class_exists($class)) {
                new $class;
            }
        }
    }
}
