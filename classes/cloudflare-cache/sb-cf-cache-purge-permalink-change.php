<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Class SB_CF_Cache_Purge_Permalink_Change
 */
class SB_CF_Cache_Purge_Permalink_Change {

    /**
     * Current state of the permalink before post update.
     *
     * @var null
     */
    private $previous_post_permalink = null;

    /**
     * SB_CF_Cache_Purge_Permalink_Change constructor.
     */
    public function __construct() {
        $this->register_actions();
    }

    /**
     * Register action hooks.
     */
    private function register_actions() {

        // Check if Cloudflare cache purge feature is active
        if ( ! sb_cf_cache()->cf_is_active() ) return;

        if ( ! apply_filters('sb_optimizer_automatic_purge_on_permalink_change', true) ) return;

        // Purge old term permalink if slug is changed
        if ( apply_filters('sb_optimizer_automatic_purge_on_term_permalink_change', true) ) {
            add_filter( 'wp_update_term_data', [$this, 'check_previous_term_permalink'], 10, 3 );
        }

        // Purge old post permalink if slug is changed
        if ( apply_filters('sb_optimizer_automatic_purge_on_post_permalink_change', true) ) {
            add_action( 'pre_post_update', [$this, 'record_post_permalink'], 99, 1 );
            add_action( 'post_updated', [$this, 'check_previous_post_permalink'], 99, 1 );
        }

    }

    /**
     * Check if the term permalink changed, and if so then purge the old one.
     *
     * @param $update_data
     * @param $term_id
     * @param $taxonomy
     * @return mixed
     */
    public function check_previous_term_permalink($update_data, $term_id, $taxonomy) {
        $term = get_term( $term_id, $taxonomy );
        $term_slug_did_change = $term->slug !== $update_data['slug'];
        if ( $term_slug_did_change ) {
            $previous_term_permalink = get_term_link($term);
            if ( $previous_term_permalink ) {
                sb_cf_cache()->purge_by_url($previous_term_permalink);
            }
        }
        return $update_data;
    }

    /**
     * Record the current state of the permalink before post update.
     *
     * @param $post_id
     */
    public function record_post_permalink($post_id) {
        $this->previous_post_permalink = get_permalink($post_id);
    }

    /**
     * Check if the permalink changed.
     *
     * @param $post_id
     * @return bool
     */
    public function post_permalink_did_change($post_id)
    {
        if ( ! is_null($this->previous_post_permalink) && get_permalink($post_id) !== $this->previous_post_permalink ) {
            return true;
        }
        return false;
    }

    /**
     * Check if the post permalink changed, and if so then purge the old one.
     *
     * @param $post_id
     */
    public function check_previous_post_permalink($post_id) {
        if ( $this->post_permalink_did_change($post_id) ) {
            sb_cf_cache()->purge_by_url($this->previous_post_permalink);
        }
    }

}
new SB_CF_Cache_Purge_Permalink_Change;
