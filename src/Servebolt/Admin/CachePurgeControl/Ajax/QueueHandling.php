<?php

namespace Servebolt\Optimizer\Admin\CachePurgeControl\Ajax;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Admin\SharedAjaxMethods;
use function Servebolt\Optimizer\Helpers\arrayGet;
use function Servebolt\Optimizer\Helpers\ajaxUserAllowed;

class QueueHandling extends SharedAjaxMethods
{

    /**
     * CachePurgeQueue constructor.
     */
    public function __construct()
    {
        add_action('wp_ajax_servebolt_delete_cache_purge_queue_items', [$this, 'deleteCachePurgeQueueItemsCallback']);
        add_action('wp_ajax_servebolt_load_cache_purge_queue_list', [$this, 'loadCachePurgeQueueList']);
    }

    /**
     * Delete items from cache purge queue.
     */
    public function deleteCachePurgeQueueItemsCallback()
    {
        $this->checkAjaxReferer();
        ajaxUserAllowed();

        $itemsToRemove = arrayGet('items_to_remove', $_POST);

        // Check if post data is present
        if (!$itemsToRemove || empty($itemsToRemove) ) {
            wp_send_json_error();
        }

        // Clear all queue items
        if ($itemsToRemove === 'flush') {
            sb_cf_cache()->clear_items_to_purge();
            wp_send_json_success();
        }

        // Check if invalid post data was passed
        if ( ! is_array($itemsToRemove) ) {
            wp_send_json_error();
        }

        // Prevent invalid items to pass through
        $itemsToRemove = array_filter($itemsToRemove, function ($item) {
            return is_string($item);
        });

        // Reformat items to remove
        $itemsToRemove = array_map(function($item) {
            $parts = explode('-', $item, 2);
            if ( count($parts) !== 2 ) return false;
            list($type, $item) = $parts;
            return compact('type', 'item');
        }, $itemsToRemove);

        // Remove invalid ones
        $itemsToRemove = array_filter($itemsToRemove, function($item) {
            return $item !== false;
        });

        sb_cf_cache()->filter_out_items_from_queue($itemsToRemove);

        wp_send_json_success();
    }

    /**
     * Generate cache purge queue list table markup.
     */
    public function loadCachePurgeQueueList()
    {
        $this->checkAjaxReferer();;
        ajaxUserAllowed();
        $maxNumberOfCachePurgeQueueItems = sb_cf_cache_admin_controls()->maxNumberOfCachePurgeQueueItems();
        $itemsToPurge = sb_cf_cache()->getItemsToPurge($maxNumberOfCachePurgeQueueItems);
        wp_send_json_success([
            'html' => sb_cf_cache_admin_controls()->purgeQueueList($itemsToPurge, false),
        ]);
    }
}
