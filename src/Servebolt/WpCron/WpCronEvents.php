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

    /**
     * Register events.
     */
    private function registerEvents(): void
    {
        //$events = glob(__DIR__ . '/Events/*.php');
        $events = [
            __DIR__ . '/Events/QueueParseEvent.php',
        ];
        foreach($events as $file) {
            $class = '\\Servebolt\\Optimizer\\WpCron\\Events\\' . basename($file, '.php');
            if (class_exists($class)) {
                new $class;
            }
        }
    }
}
