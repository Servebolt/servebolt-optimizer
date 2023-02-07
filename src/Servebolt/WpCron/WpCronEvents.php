<?php

namespace Servebolt\Optimizer\WpCron;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Class WpWpCron
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
        //Names of calsses in Events directory
        $events = [
            'QueueParseEvent',
            'QueueGarbageCollectionEvent',
            'ClearExpiredTransients',
        ];
        foreach($events as $className) {
            $class = '\\Servebolt\\Optimizer\\WpCron\\Events\\' . $className;
            if (class_exists($class)) {
                new $class;
            }
        }
    }

}
