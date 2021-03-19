<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

use function Servebolt\Optimizer\Helpers\isUrl;

/**
 * Class CF_Cache_Purge_Queue_Item
 *
 * This class is used to store an queue item that are waiting to be purged by the cron. The item can be resolved into a purge object as long as it is a reference to a URL, post or term. "Purge all"-items are not resolved to a purge object.
 */
class CF_Cache_Purge_Queue_Item {

    /**
     * The type of the item.
     *
     * @var mixed
     */
    private $type = null;

    /**
     * The value of the item (term Id, post Id, URL).
     * @var mixed
     */
    private $item = null;

    /**
     * The timestamp the item was added to the queue.
     *
     * @var mixed
     */
    private $timestamp = null;

    /**
     * The purge object (object containing the URLs related to the purge items etc.).
     *
     * @var null
     */
    private $purge_object = null;

    /**
     * CF_Cache_Purge_Queue_Item constructor.
     *
     * @param $item
     */
    public function __construct($item) {
        $this->register_properties($item);
    }

    /**
     * Register properties in class.
     *
     * @param $item
     */
    private function register_properties($item) {
        $this->type = $item['type'];
        $this->item = $item['item'];
        $this->timestamp = $item['timestamp'];
    }

    /**
     * Get the UNIX timestamp when this purge queue item was added.
     *
     * @return mixed
     */
    public function get_time_added() {
        return $this->timestamp;
    }

    /**
     * Get the datetime when this purge queue item was added.
     *
     * @return mixed|null
     */
    public function get_datetime_added() {
        if ( $this->get_time_added() ) {
            return date_i18n('Y-m-d H:i:s', $this->get_time_added());
        }
        return null;
    }

    /**
     * Get the datetime when this purge queue item was added.
     *
     * @param bool $ignore_all
     * @return string|null
     */
    public function get_item_type($ignore_all = true) {
        if ( $this->type == 'all' && $ignore_all ) {
            return null;
        }
        return ucfirst($this->type);
    }

    /**
     * Get the identifier for this purge item.
     *
     * @return string
     */
    public function get_identifier() {
        return $this->type . '-' . $this->item;
    }

    /**
     * @return array|bool|bool[]|mixed
     */
    public function get_purge_urls() {
        switch ( $this->type ) {
            case 'all':
                return [ 'all' ];
            case 'url':
                return [ $this->get_url() ]; // Only one URL
            default:
                return ( $this->get_purge_object() )->get_purge_urls();
        }

    }

    /**
     * Get the purge object.
     *
     * @return SB_CF_Cache_Purge_Object
     */
    private function get_purge_object() {
        if ( is_null($this->purge_object) ) {
            $this->purge_object = sb_cf_cache_purge_object($this->item, $this->type);
        }
        return $this->purge_object;
    }

    /**
     * Get the URL of the purge object.
     *
     * @return bool|mixed|void
     */
    public function get_url() {
        if ( ! $this->is_clickable() || $this->is_url() ) return $this->item;
        if ( $purge_object = $this->get_purge_object() ) {
            return $purge_object->get_base_url();
        }
        return false;
    }

    /**
     * Get the edit URL (if present) of the purge object.
     *
     * @return bool|mixed|void
     */
    public function get_edit_url() {
        if ( ! $this->is_editable() ) return false;
        if ( $this->get_purge_object() ) {
            return $this->get_purge_object()->get_edit_url();
        }
        return false;
    }

    /**
     * Get the title (if present) of the purge object.
     *
     * @return bool|mixed|void
     */
    public function get_title() {
        if ( $this->is_url() ) return $this->item;
        if ( $this->get_purge_object() ) {
            return $this->get_purge_object()->get_title();
        }
        return false;
    }

    /**
     * Get the Id (if present) of the purge object.
     *
     * @return bool|mixed|void
     */
    public function get_id() {
        if ( $this->is_url() ) return false;
        if ( $this->get_purge_object() ) {
            return $this->get_purge_object()->get_id();
        }
        return false;
    }

    /**
     * Whether this item is a purge all item.
     *
     * @return bool
     */
    public function is_purge_all_item() {
        return $this->type === 'all';
    }

    /**
     * Whether this item can be clicked (if it has a URL).
     *
     * @return bool
     */
    public function is_clickable() {
        return $this->is_url() || $this->is_wp_object();
    }

    /**
     * Whether this purge item can be edited or not.
     *
     * @return bool
     */
    public function is_editable() {
        return $this->is_wp_object();
    }

    /**
     * Whether this item has a reference to a WP-object.
     *
     * @return bool
     */
    public function is_wp_object() {
        return $this->is_post() || $this->is_term();
    }

    /**
     * Whether this item is a URL.
     *
     * @return bool
     */
    public function is_url() {
        return isUrl($this->item);
    }

    /**
     * Whether this item is a post.
     *
     * @return bool
     */
    public function is_post() {
        return $this->type === 'post';
    }

    /**
     * Whether this item is a term.
     *
     * @return bool
     */
    public function is_term() {
        return $this->type === 'term';
    }

}
