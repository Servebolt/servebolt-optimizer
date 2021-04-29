<?php

namespace Servebolt\Optimizer\Cli\Cache;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use WP_CLI;
use function Servebolt\Optimizer\Helpers\booleanToString;
use function WP_CLI\Utils\format_items as WP_CLI_FormatItems;
use Servebolt\Optimizer\Cli\CliHelpers;
use Servebolt\Optimizer\Queue\Queues\WpObjectQueue;
use Servebolt\Optimizer\Queue\Queues\UrlQueue;
use function Servebolt\Optimizer\Helpers\iterateSites;

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
     * [--format=<format>]
     * : Return format.
     * ---
     * default: text
     * options:
     *   - text
     *   - json
     * ---
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
            $statusArray = [];
            iterateSites(function ($site) use (&$statusArray) {
                $this->clearCachePurgeQueues();
                $message = sprintf(__('Cache purge queue cleared on site %s.', 'servebolt-wp'), get_site_url($site->blog_id));
                if (CliHelpers::returnJson()) {
                    $statusArray[] = [
                        'blog_id' => $site->blog_id,
                        'success' => true,
                        'message' => $message,
                    ];
                } else {
                    $statusArray[] = [
                        'Blog' => get_site_url($site->blog_id),
                        'Success' => booleanToString(true),
                        'Message' => $message,
                    ];
                }
            }, true);
            if (CliHelpers::returnJson()) {
                CliHelpers::printJson($statusArray);
            } else {
                WP_CLI_FormatItems('table', $statusArray, array_keys(current($statusArray)));
            }
        } else {
            $this->clearCachePurgeQueues();
            $message = __('Cache purge queue cleared!', 'servebolt-wp');
            if (CliHelpers::returnJson()) {
                CliHelpers::printJson([
                    'success' => true,
                    'message' => $message,
                ]);
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
