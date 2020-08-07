<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Class SB_CF_Cache_Purge_Term_Object
 *
 * This is a cache purge object with the type of term.
 */
class SB_CF_Cache_Purge_Term_Object extends SB_CF_Cache_Purge_Object_Shared {

    /**
     * Define the type of this object in WP context.
     *
     * @var string
     */
    protected $object_type = 'term';

    /**
     * SB_CF_Cache_Purge_Term_Object constructor.
     * @param $term_id
     */
    public function __construct($term_id) {
        parent::__construct($term_id);
    }

    /**
     * Add URLs related to a term object.
     */
    protected function init_object() {

        // The URL to the term archive.
        if ( $this->add_term_url() ) {
            $this->success(true); // Flag that found the term
            return true;
        } else {
            return false; // Could not find the term, stop execution
        }

    }

    /**
     * Generate URLs related to the object.
     */
    protected function generate_other_urls() {

        // The URL to the front page
        $this->add_front_page();

    }

    /**
     * Add the term URL.
     */
    private function add_term_url() {
        $term_url = get_term_link( $this->get_id() );
        if ( $term_url && ! is_wp_error($term_url) ) {
            $this->add_urls(sb_paginate_links_as_array($term_url, $this->get_pages_needed($term_url)));
            return true;
        }
        return false;
    }
}
