<?php

namespace Servebolt\Optimizer\Compatibility\ActionScheduler;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use function Servebolt\Optimizer\Helpers\actionSchedulerIsActive;
use function Servebolt\Optimizer\Helpers\isHostedAtServebolt;

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
        if (!isHostedAtServebolt()) {
            return; // Servebolt-only feature
        }
        if (!actionSchedulerIsActive()) {
            return;
        }
        new DisableActionSchedulerDefaultRunner;
    }
}
