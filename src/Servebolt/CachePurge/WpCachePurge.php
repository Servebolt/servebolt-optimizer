<?php

namespace Servebolt\Optimizer\CachePurge;

use Servebolt\Optimizer\CachePurge\WpObjectCachePurgeActions\WpObjectCachePurgeActions;
use function Servebolt\Optimizer\Helpers\isCli;
use function Servebolt\Optimizer\Helpers\isCron;
use function Servebolt\Optimizer\Helpers\isTesting;
use function Servebolt\Optimizer\Helpers\isWpRest;
use function Servebolt\Optimizer\Helpers\setDefaultOption;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Class WpCachePurge
 * @package Servebolt\Optimizer\CachePurge
 */
class WpCachePurge
{
    /**
     * WpCachePurge constructor.
     */
    public function __construct()
    {
        $this->defaultOptionValues();

        if (
            is_admin()
            || isCron()
            || isCli()
            || isWpRest()
            || isTesting()
        ) {
            // Register cache purge event for various hooks
            WpObjectCachePurgeActions::on();
        }
    }

    /**
     * Set default option values.
     */
    private function defaultOptionValues(): void
    {
        setDefaultOption('cache_purge_auto', '__return_true');
        setDefaultOption('cache_purge_auto_on_slug_change', '__return_true');
        setDefaultOption('cache_purge_auto_on_deletion', '__return_true');
    }
}
