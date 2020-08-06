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
     * SB_CF_Cache_Purge_Object_Shared constructor.
     *
     * @param $id
     */
    protected function __construct($id) {
        $this->set_id($id);
        $this->init_object();
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
     * Get the ID of the object to be pured.
     *
     * @return mixed
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * Add URL to be purged cache for.
     *
     * @param $url
     * @return bool
     */
    protected function add_url($url) {
        if ( ! in_array($url, $this->urls) ) {
            $this->urls[] = $url;
            return true;
        }
        return false;
    }

    /**
     * Add multiple URLs to be purged cache for.
     *
     * @param $urls
     */
    protected function add_urls($urls) {
        array_map(function ($url) {
            $this->add_url($url);
        }, $urls);
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
     * Query and find out how many pages a given archive URL has.
     *
     * @param $url
     * @return bool
     */
    protected function get_pages_needed($url) {
        $url .= '?' . http_build_query([
            'sb_optimizer_record_max_num_pages' => '',
            'cachebust' => microtime(true),
        ]);
        $response = wp_remote_get($url, [
            'httpversion' => '1.1',
            'blocking'    => true,
            'timeout'     => 10,
            'sslverify'   => false,
        ]);
        $json = json_decode(wp_remote_retrieve_body($response)) ?: [];
        if ( isset($json->max_num_pages) ) {
            return $json->max_num_pages;
        }
        return false;
    }

}
