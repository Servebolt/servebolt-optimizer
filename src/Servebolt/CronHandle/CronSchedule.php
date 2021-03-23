<?php

namespace Servebolt\Optimizer\CronHandle;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Class CronSchedule
 * @package Servebolt\Optimizer\CronHandle
 */
class CronSchedule
{
    /**
     * CronSchedule constructor.
     */
    public function __construct()
    {
        add_filter('cron_schedules', [$this, 'addScheduleEveryMinute']);
    }

    public function addScheduleEveryMinute(array $schedules): array
    {
        $schedules['every_minute'] = [
            'interval' => 60,
            'display'  => __('Every minute', 'servebolt-wp')
        ];
        return $schedules;
    }
}
