<?php

namespace Servebolt\Optimizer\WpCron;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Class WpCronCustomSchedules
 * @package Servebolt\Optimizer\WpCron
 */
class WpCronCustomSchedules
{
    /**
     * WpCronCustomSchedules constructor.
     */
    public function __construct()
    {
        add_filter('cron_schedules', [$this, 'registerMinuteIntervalSchedule']);
    }

    public function registerMinuteIntervalSchedule(array $schedules): array
    {
        $schedules['every_minute'] = [
            'interval' => 60,
            'display'  => __('Every minute', 'servebolt-wp')
        ];
        return $schedules;
    }
}
