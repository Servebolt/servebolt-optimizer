<?php

namespace Servebolt\Optimizer\Admin\CachePurgeControl\Ajax;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use Servebolt\Optimizer\Admin\SharedAjaxMethods;

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
    public function deleteCachePurgeQueueItemsCallback() {
        check_ajax_referer( sb_get_ajax_nonce_key(), 'security' );
        sb_ajax_user_allowed();

        $items_to_remove = sb_array_get('items_to_remove', $_POST);

        // Check if post data is present
        if ( ! $items_to_remove || empty($items_to_remove) ) {
            wp_send_json_error();
        }

        // Clear all queue items
        if ( $items_to_remove === 'flush' ) {
            sb_cf_cache()->clear_items_to_purge();
            wp_send_json_success();
        }

        // Check if invalid post data was passed
        if ( ! is_array($items_to_remove) ) {
            wp_send_json_error();
        }

        // Prevent invalid items to pass through
        $items_to_remove = array_filter($items_to_remove, function ($item) {
            return is_string($item);
        });

        // Reformat items to remove
        $items_to_remove = array_map(function($item) {
            $parts = explode('-', $item, 2);
            if ( count($parts) !== 2 ) return false;
            list($type, $item) = $parts;
            return compact('type', 'item');
        }, $items_to_remove);

        // Remove invalid ones
        $items_to_remove = array_filter($items_to_remove, function($item) {
            return $item !== false;
        });

        sb_cf_cache()->filter_out_items_from_queue($items_to_remove);

        wp_send_json_success();
    }

    /**
     * Generate cache purge queue list table markup.
     */
    public function loadCachePurgeQueueList()
    {
        $this->checkAjaxReferer();;
        sb_ajax_user_allowed();
        $max_number_of_cache_purge_queue_items = sb_cf_cache_admin_controls()->max_number_of_cache_purge_queue_items();
        $items_to_purge = sb_cf_cache()->get_items_to_purge($max_number_of_cache_purge_queue_items);
        wp_send_json_success([
            'html' => sb_cf_cache_admin_controls()->purge_queue_list($items_to_purge, false),
        ]);
    }
}
