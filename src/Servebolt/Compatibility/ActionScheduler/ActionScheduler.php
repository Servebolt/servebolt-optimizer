<?php

namespace Servebolt\Optimizer\Compatibility\ActionScheduler;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use function Servebolt\Optimizer\Helpers\actionSchedulerIsActive;

/**
 * Class ActionScheduler
 * @package Servebolt\Optimizer\Compatibility\ActionScheduler
 */
class ActionScheduler
{
    /**
     * ActionScheduler constructor.
     */
    public function __construct()
    {
        
        if (!apply_filters('sb_optimizer_action_scheduler_compatibility', true)) {
            return;
        }
        if (!actionSchedulerIsActive()) {
            return;
        }

        add_filter('action_scheduler_queue_runner_batch_size', [$this, 'batch_size']);
        add_filter('action_scheduler_queue_runner_concurrent_batches', [$this, 'concurrent_batches']);
        add_filter('action_scheduler_queue_runner_time_limit', [$this, 'time_limit']);

        new DisableActionSchedulerDefaultRunner;
    }

    /**
     * Increase default batch size from 25 to 100.
     * @return int
     */
    public function batch_size($batch_size)
    {
        return 100;
    }
    
    /**
     * Increase number of batch run at once from 1 to 2.
     * @return int
     */
    public function concurrent_batches($concurrent_batches)
    {
        return 2;
    }
    
    /**
     * Increase the timeout of a batch from 30 seconds to 55seconds.
     * @return int
     */
    public function time_limit($time_limit)
    {
        return 55;
    }

}
