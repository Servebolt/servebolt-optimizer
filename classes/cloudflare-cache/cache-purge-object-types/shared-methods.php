<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Trait SB_CF_Cache_Purge_Object_Shared_Methods
 *
 * This trait contains common methods for building a cache purge object of various types (post, term etc.).
 */
abstract class SB_CF_Cache_Purge_Object_Shared {

    /**
     * The ID of the object to be purged.
     *
     * @var
     */
    private $id;

    /**
     * The URLs related to the object about to be purged.
     *
     * @var array
     */
    private $urls = [];

    /**
     * Array of additional arguments for the object to be purged.
     *
     * @var array
     */
    private $args = [];

    /**
     * Whether we could resolve the purge object from the ID (post/term lookup).
     *
     * @var null
     */
    private $success = false;

    /**
     * SB_CF_Cache_Purge_Object_Shared constructor.
     *
     * @param $id
     * @param $args
     */
    protected function __construct($id, $args) {
        $this->set_id($id);
        $this->set_arguments($args);
        if ( $this->init_object() ) { // Check if we could find the object first
            if ( apply_filters('sb_optimizer_should_generate_other_urls', true) ) { // Check if we should generate all other related URLs for object
                $this->generate_other_urls();
            }
        }
        $this->post_url_generate_actions();
    }

    /**
     * Do stuff after we have generated URLs.
     */
    private function post_url_generate_actions() {

        // Let other manipulate URLs
        $this->set_urls(apply_filters('sb_optimizer_alter_urls_for_cache_purge_object', $this->get_urls(), $this->get_id(), $this->object_type));

    }

    /**
     * Set/get whether we could resolve the purge object from the ID (post/term lookup).
     *
     * @param null $bool
     * @return bool|void
     */
    public function success($bool = null) {
        if ( is_bool($bool) ) {
            $this->success = $bool;
            return;
        }
        return $this->success === true;
    }

    /**
     * Set the ID of the object to be pured.
     *
     * @param $id
     */
    protected function set_id($id) {
        $this->id = $id;
    }

    /**
     * Set the arguments of the object to be pured.
     *
     * @param $args
     */
    protected function set_arguments($args) {
        $this->args = $args;
    }

    /**
     * Get and argument for the object to be purged.
     *
     * @param $key
     * @return bool
     */
    protected function get_argument($key) {
        if ( array_key_exists($key, $this->args) ) {
            return $this->args[$key];
        }
        return false;
    }

    /**
     * Get the ID of the object to be pured.
     *
     * @return mixed
     */
    public function get_id() {
        if ( is_numeric($this->id) ) {
            return (int) $this->id; // Make sure to return ID as int if it is numerical
        }
        return $this->id;
    }

    /**
     * Add URL to be purged cache for.
     *
     * @param $url
     * @return bool
     */
    public function add_url($url) {
        $urls = $this->get_urls();
        if ( ! in_array($url, $urls) ) {
            $urls[] = $url;
            $this->set_urls($urls);
            return true;
        }
        return false;
    }

    /**
     * Add multiple URLs to be purged cache for.
     *
     * @param $urls
     */
    public function add_urls($urls) {
        if ( ! is_array($urls) ) return;
        array_map(function ($url) {
            $this->add_url($url);
        }, $urls);
    }

    /**
     * Set the URLs to purge cache for.
     *
     * @param $urls
     */
    public function set_urls($urls) {
        if ( ! is_array($urls) ) return false;
        $this->urls = $urls;
        return true;
    }

    /**
     * Get the URLs to purge cache for.
     *
     * @return array
     */
    public function get_urls() {
        return $this->urls;
    }

    /**
     * Add the front page to be purged.
     */
    public function add_front_page() {
        if ( $front_page_id = get_option( 'page_on_front' ) ) {
            $front_page_url = get_permalink($front_page_id);
            if ( $front_page_url ) {
                $this->add_url($front_page_url);
            }
        }
    }

    /**
     * Find out how many pages needed for an archive.
     *
     * @param $query_args
     * @param $type
     * @return mixed
     */
    protected function get_pages_needed($query_args, $type) {
        $query_args = wp_parse_args($query_args, [
            'post_type'   => 'any',
            'post_status' => 'publish',
        ]);
        $query_args = (array) apply_filters('sb_optimizer_cf_cache_purge_pages_needed_post_query_arguments', $query_args, $type, $this->get_id());
        $query = new WP_Query($query_args);
        if ( ! $query->have_posts() ) {
            return apply_filters('sb_optimizer_cf_cache_purge_pages_needed_no_posts', 0, $type, $this->get_id());
        }
        return apply_filters('sb_optimizer_cf_cache_purge_pages_needed', $query->max_num_pages, $type, $this->get_id());
    }

}
