<?php

namespace Servebolt\Optimizer\Compatibility\WooCommerce;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Class DisableActionScheduler
 * @package Servebolt\Optimizer\Compatibility\WooCommerce
 */
class DisableActionScheduler
{
    /**
     * DisableActionScheduler constructor.
     */
    public function __construct()
    {
        add_action('init', [$this, 'disableDefaultRunner']);
    }

    public function disableDefaultRunner(): void
    {
        if (class_exists('ActionScheduler')) {
            remove_action('action_scheduler_run_queue', [ActionScheduler::runner(), 'run']);
        }
    }
}
