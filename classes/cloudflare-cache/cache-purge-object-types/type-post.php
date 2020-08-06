<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Class SB_CF_Cache_Purge_Post_Object
 *
 * This is a cache purge object with the type of post.
 */
class SB_CF_Cache_Purge_Post_Object extends SB_CF_Cache_Purge_Object_Shared {

    /**
     * SB_CF_Cache_Purge_Post_Object constructor.
     * @param $post_id
     */
    public function __construct($post_id) {
        parent::__construct($post_id);
    }

    /**
     * Add URLs related to a post object.
     */
    protected function init_object() {

        // The URL to the front page
        $this->add_front_page();

        // The URL to the post itself
        $this->add_post_url();

        // The URL to the post type archive for the post type of the post
        $this->add_post_type_archive();

        // The URL to the author archive
        $this->add_author_archive();

        // The URLs for categories, tags, post formats + any custom taxonomies for post type
        $this->add_taxonomy_archives();

        // Only for post type "post"
        if ( $this->post_type_is('post') ) {

            // The URL to the date archive
            $this->add_date_archive();

        }
    }

    /**
     * Add the URL of a post to be purged.
     */
    private function add_post_url() {
        if ( $post_permalink = get_permalink( $this->get_id() ) ) {
            $this->add_url($post_permalink);
        }
    }

    /**
     *
     */
    private function add_post_type_archive() {
        if ( $post_type_archive_url = get_post_type_archive_link($this->get_post_type()) ) {
            $this->add_urls(sb_paginate_links_as_array($post_type_archive_url, $this->get_pages_needed($post_type_archive_url)));
        }
    }

    /**
     * Add author URL to be purged.
     */
    private function add_author_archive() {
        if ( $author = $this->get_post_author() ) {
            $author_url = get_author_posts_url($author);
            if ( $author_url ) {
                $this->add_urls(sb_paginate_links_as_array($author_url, $this->get_pages_needed($author_url)));
            }
        }
    }

    /**
     *
     */
    private function add_taxonomy_archives() {
        $taxonomies = get_taxonomies( [ 'object_type' => [ $this->get_post_type() ] ] );
        foreach ( $taxonomies as $taxonomy_slug => $taxonomy_name ) {
            $terms = wp_get_post_terms($this->object_id, $taxonomy_slug);
            foreach ( $terms as $term ) {
                if ( $term_link = get_term_link($term, $taxonomy_slug) ) {
                    $this->add_urls(sb_paginate_links_as_array($term_link, $this->get_pages_needed($term_link)));
                }
            }
        }
    }

    /**
     *
     */
    private function add_date_archive() {
        $year  = get_the_time('Y', $this->object_id);
        $month = get_the_time('m', $this->object_id);
        $day   = get_the_time('d', $this->object_id);
        if ( $date_archive = get_day_link($year, $month, $day) ) {
            $this->add_urls(sb_paginate_links_as_array($date_archive, $this->get_pages_needed($date_archive)));
        }
    }

    /**
     * Get the author of a post.
     *
     * @return mixed
     */
    private function get_post_author() {
        if ( $this->object_type !== 'post' ) return false;
        $post = get_post( $this->object_id );
        return $post->post_author;
    }

    /**
     * Get the post type of the post object.
     *
     * @return mixed
     */
    private function get_post_type() {
        return get_post_type($this->get_id());
    }

    /**
     * Check if current post type is equals a given post type.
     *
     * @param $post_type
     * @return bool
     */
    private function post_type_is($post_type) {
        return $post_type == $this->get_post_type();
    }

}
