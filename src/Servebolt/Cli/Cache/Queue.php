<?php

namespace Servebolt\Optimizer\Cli\Cache;

use WP_CLI;
use Servebolt\Optimizer\Cli\CliHelpers;
use Servebolt\Optimizer\Queue\Queues\WpObjectQueue;
use Servebolt\Optimizer\Queue\Queues\UrlQueue;
use function Servebolt\Optimizer\Helpers\iterateSites;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Class Queue
 * @package Servebolt\Optimizer\Cli\Cache
 */
class Queue
{
    /**
     * Queue constructor.
     */
    public function __construct()
    {
        WP_CLI::add_command('servebolt cache purge queue clear', [$this, 'clearCachePurgeQueue']);
    }

    /**
     * Clear cache purge queue.
     *
     * ## OPTIONS
     *
     * [--all]
     * : Clear cache purge queue on all sites in multisite.
     *
     * ## EXAMPLES
     *
     *     # Clear cache purge queue
     *     wp servebolt cache purge queue clear
     *
     *     # Clear cache purge queue on all sites in a multisite
     *     wp servebolt cache purge queue clear --all
     *
     */
    public function clearCachePurgeQueue($args, $assocArgs)
    {
        CliHelpers::setReturnJson($assocArgs);
        if (CliHelpers::affectAllSites($assocArgs)) {
            iterateSites(function ($site) use (&$settings) {
                $this->clearCachePurgeQueues();
                $message = sprintf(__('Cache purge queue cleared on site %s.', 'servebolt-wp'), get_site_url($site->blog_id));
                if (CliHelpers::returnJson()) {
                    CliHelpers::printJson(compact('message'));
                } else {
                    WP_CLI::success($message);
                }
            }, true);
        } else {
            $this->clearCachePurgeQueues();
            $message = __('Cache purge queue cleared!', 'servebolt-wp');
            if (CliHelpers::returnJson()) {
                CliHelpers::printJson(compact('message'));
            } else {
                WP_CLI::success($message);
            }
        }
    }

    /**
     * Clear cache purge queues.
     */
    private function clearCachePurgeQueues(): void
    {
        (new WpObjectQueue)->clearQueue();
        (new UrlQueue)->clearQueue();
    }
}
