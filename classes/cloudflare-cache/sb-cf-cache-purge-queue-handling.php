<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Class Servebolt_CF_Cache_Purge_Queue_Handling
 */
class Servebolt_CF_Cache_Purge_Queue_Handling {

    /**
     * The key to be used when scheduling CF cache purge.
     *
     * @var string
     */
    private $purge_cron_key = 'servebolt_cron_hook_purge_by_cron';

    /**
     * The key to be used when scheduling CF cache purge queue cleaning.
     *
     * @var string
     */
    private $purge_queue_clean_cron_key = 'servebolt_cron_hook_purge_by_cron_queue_clean';

    /**
     * Get cron key used for scheduling Cloudflare cache purge.
     *
     * @return string
     */
    public function get_purge_cron_key() {
        return $this->purge_cron_key;
    }

    /**
     * Get cron key used for scheduling Cloudflare cache purge.
     *
     * @return string
     */
    public function get_purge_queue_clean_cron_key() {
        return $this->purge_queue_clean_cron_key;
    }

    /**
     * Check if a purge all request is queued.
     *
     * @return bool
     */
    public function has_purge_all_request_in_queue() {
        $items_to_purge = $this->get_items_to_purge();
        foreach ( $items_to_purge as $item_to_purge ) {
            if ( $item_to_purge->is_purge_all_item() ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if we should clean the cache purge queue based on the age of each item.
     *
     * @return mixed
     */
    public function should_clean_cache_purge_queue() {
        if ( defined('SERVEBOLT_CF_PURGE_CRON_CLEANER_ACTIVE') && is_bool(SERVEBOLT_CF_PURGE_CRON_CLEANER_ACTIVE) ) {
            return SERVEBOLT_CF_PURGE_CRON_CLEANER_ACTIVE;
        }
        return apply_filters('sb_optimizer_should_clean_cache_purge_queue', false);
    }

    /**
     * Check whether we have overridden the "should_purge_cache_queue" value.
     *
     * @return bool
     */
    public function should_purge_cache_queue_is_overridden() {
        return defined('SERVEBOLT_CF_PURGE_CRON_PARSE_QUEUE') && is_bool(SERVEBOLT_CF_PURGE_CRON_PARSE_QUEUE);
    }

    /**
     * Check whether we should execute cron purge with cron or not. This can be used to only schedule purges but not execute them in a system.
     *
     * @return bool
     */
    public function should_purge_cache_queue() {
        if ( $this->should_purge_cache_queue_is_overridden() ) {
            return SERVEBOLT_CF_PURGE_CRON_PARSE_QUEUE;
        }
        return apply_filters('sb_optimizer_should_purge_cache_queue', true);
    }

    /**
     * Check whether we have set a boolean override value for the cron.
     *
     * @return bool
     */
    public function cron_state_is_overridden() {
        return defined('SERVEBOLT_CF_PURGE_CRON') && is_bool(SERVEBOLT_CF_PURGE_CRON);
    }

    /**
     * Check if we have overridden whether the Cron purge should be active or not.
     *
     * @return mixed
     */
    public function cron_active_state_override() {
        if ( $this->cron_state_is_overridden() ) {
            return SERVEBOLT_CF_PURGE_CRON;
        }
        return null;
    }

    /**
     * Check whether the Cron-based cache purger should be active.
     *
     * @param bool $respect_override
     * @param bool $blog_id
     *
     * @return bool|mixed
     */
    public function cron_purge_is_active($respect_override = true, $blog_id = false) {
        $blog_id = $this->get_blog_id($blog_id);
        $active_state_override = $this->cron_active_state_override();
        if ( $respect_override && is_bool($active_state_override) ) {
            return $active_state_override;
        }
        if ( is_numeric($blog_id) ) {
            $value = sb_get_blog_option($blog_id, $this->cf_cron_active_option_key());
        } else {
            $value = sb_get_option($this->cf_cron_active_option_key());
        }
        return sb_checkbox_true($value);
    }

    /**
     * The option name/key we use to store the active state for the Cloudflare cache cron purge feature.
     *
     * @return string
     */
    private function cf_cron_active_option_key() {
        return 'cf_cron_purge';
    }

    /**
     * Toggle whether Cloudflare cache purge cron should be active or not.
     *
     * @param bool $state
     * @param bool $blog_id
     *
     * @return bool|mixed
     */
    public function cf_toggle_cron_active(bool $state, $blog_id = false) {
        $blog_id = $this->get_blog_id($blog_id);
        if ( is_numeric($blog_id) ) {
            return sb_update_blog_option($blog_id, $this->cf_cron_active_option_key(), $state);
        } else {
            return sb_update_option($this->cf_cron_active_option_key(), $state);
        }
    }

    /**
     * Clear items to purge.
     */
    public function clear_items_to_purge() {
        $this->set_items_to_purge([]);
    }

    /**
     * Reformat and filter out items from the cache purge queue.
     *
     * @param $items_to_remove
     * @param bool $do_save
     * @return array|CF_Cache_Purge_Queue_Item[]
     */
    public function filter_out_items_from_queue($items_to_remove, $do_save = true) {

        // Get current items
        $current_items = sb_cf_cache()->get_items_to_purge_unformatted();
        if ( ! is_array($current_items) ) $current_items = [];

        // Filter out items
        $updated_items = array_filter($current_items, function($item) use ($items_to_remove) {
            foreach ( $items_to_remove as $item_to_remove ) {
                $identifier_1 = $item['type'] . '-' . $item['item'];
                $identifier_2 = is_a($item_to_remove, 'CF_Cache_Purge_Queue_Item') ? $item_to_remove->get_identifier() : $item_to_remove['type'] . '-' . $item_to_remove['item']; // Handles queue items in both array and object-form
                if ( $identifier_1 == $identifier_2 ) {
                    return false; // We want to remove this item
                }
            }
            return true;
        });

        if ( $do_save ) {
            sb_cf_cache()->set_items_to_purge($updated_items);
        }

        return $updated_items;

    }

    /**
     * Delete items from the purge queue.
     *
     * @param $items_to_delete
     */
    public function delete_items_to_purge($items_to_delete) {
        $this->filter_out_items_from_queue($items_to_delete);
    }

    /**
     * Add term item to the purge queue.
     *
     * @param $item
     * @return bool
     */
    public function add_term_item_to_purge_queue($item) {
        return $this->add_item_to_purge_queue($item, 'term');
    }

    /**
     * Add post item to the purge queue.
     *
     * @param $item
     * @return bool
     */
    public function add_post_item_to_purge_queue($item) {
        return $this->add_item_to_purge_queue($item, 'post');
    }

    /**
     * Add a post Id to the purge queue (by cron).
     *
     * @param $item
     * @param $type
     * @return bool
     */
    public function add_item_to_purge_queue($item, $type) {
        if ( $type !== 'all' && empty($item) ) return false;
        $items_to_purge_with_cron = $this->get_items_to_purge_unformatted();
        if ( $this->purge_item_already_in_queue($item, $type, $items_to_purge_with_cron) ) {
            return new WP_Error($type . '_purge_item_already_in_queue');
        }
        $items_to_purge_with_cron[] = $this->purge_queue_item_template($item, $type);
        return $this->set_items_to_purge($items_to_purge_with_cron);
    }

    /**
     * Check if an item is already in the cache purge queue.
     *
     * @param $item
     * @param $type
     * @return bool
     */
    private function purge_item_already_in_queue($item, $type) {
        $item_identifier = $type . '-' . $item;
        $items_to_purge_with_cron = $this->get_items_to_purge();
        foreach ( $items_to_purge_with_cron as $item_to_purge_with_cron ) {
            if ( $item_to_purge_with_cron->get_identifier() == $item_identifier ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Generate the array for a purge queue item.
     *
     * @param $item
     * @param $type
     * @param bool $timestamp
     * @return array
     */
    private function purge_queue_item_template($item, $type, $timestamp = false) {
        if ( $timestamp === false ) {
            $timestamp = current_time('timestamp');
        }
        return [
            'item'      => $item,
            'timestamp' => $timestamp,
            'type'      => $type,
        ];
    }

    /**
     * Queue up a request to purge all cache.
     */
    public function add_purge_all_to_purge_queue() {
        return $this->add_item_to_purge_queue(false, 'all');
    }

    /**
     * The string used to stored the purge queue in the options.
     *
     * @return string
     */
    private function cf_purge_queue_option_key() {
        return 'cf_items_to_purge';
    }

    /**
     * Set the items to purge.
     *
     * @param array $items_to_purge
     *
     * @return bool
     */
    public function set_items_to_purge( array $items_to_purge ) {
        return sb_update_option( $this->cf_purge_queue_option_key(), $items_to_purge, false );
    }

    /**
     * Count the items to purge.
     *
     * @return int
     */
    public function count_items_to_purge() {
        return count($this->get_items_to_purge());
    }

    /**
     * Check if we have items to purge.
     *
     * @return bool
     */
    public function has_items_to_purge() {
        return $this->count_items_to_purge() > 0;
    }

    /**
     * Get the (unformatted) items to purge.
     *
     * @param bool $limit
     * @param bool $blog_id
     * @return array|CF_Cache_Purge_Queue_Item[]
     */
    public function get_items_to_purge_unformatted($limit = false, $blog_id = false) {
        return $this->get_items_to_purge($limit, $blog_id, false);
    }

    /**
     * Get the items to purge.
     *
     * @param bool $limit
     * @param bool $blog_id
     * @param bool $format_items
     * @return array|CF_Cache_Purge_Queue_Item[]
     */
    public function get_items_to_purge($limit = false, $blog_id = false, $format_items = true) {
        $blog_id = $this->get_blog_id($blog_id);
        if ( is_numeric($blog_id) ) {
            $items = sb_get_blog_option($blog_id, $this->cf_purge_queue_option_key());
        } else {
            $items = sb_get_option($this->cf_purge_queue_option_key());
        }
        if ( ! is_array($items) ) return [];

        if ( is_numeric($limit) ) {
            $items = array_reverse(array_slice(array_reverse($items), 0, $limit));
        }

        // Make sure we migrate items using the old data structure
        $items = $this->ensure_backwards_compatibility($items);

        if ( $format_items ) {
            $items = $this->format_items_to_purge($items);
        }
        return $items;
    }

    /**
     * Make sure we migrate the items from the old data structure.
     *
     * @param $items
     * @return mixed
     */
    private function ensure_backwards_compatibility($items) {
        $items = array_map(function($item) {
            if ( ! is_array($item) ) {
                if ( $item == '---purge-all-request---' ) {
                    return $this->purge_queue_item_template(false, 'all', null);
                } elseif ( is_numeric($item) ) {
                    return $this->purge_queue_item_template($item, 'post', null);
                } elseif( sb_is_url($item) ) {
                    return $this->purge_queue_item_template($item, 'url', null);
                }
            }
            return $item; // Item has new data structure
        }, $items);
        return $items;
    }

    /**
     * Format cache purge queue items (ensure item type is class CF_Cache_Purge_Queue_Item).
     *
     * @param $items
     * @return array|CF_Cache_Purge_Queue_Item[]
     */
    private function format_items_to_purge($items) {
        $items = array_map(function ($item) {
            if ( ! is_a($item, 'CF_Cache_Purge_Queue_Item') ) {
                if ( ! class_exists('CF_Cache_Purge_Queue_Item') ) {
                    require_once __DIR__ . '/sb-cf-cache-purge-queue-item.php';
                }
                return new CF_Cache_Purge_Queue_Item($item);
            }
            return $item;
        }, $items);
        return $items;
    }

}
