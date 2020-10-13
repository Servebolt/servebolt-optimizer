<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Class SB_CF_Cache_Purge_Post_Object
 *
 * This is a cache purge object with the type of post.
 */
class SB_CF_Cache_Purge_Post_Object extends SB_CF_Cache_Purge_Object_Shared {

    /**
     * Define the type of this object in WP context.
     *
     * @var string
     */
    protected $object_type = 'post';

    /**
     * SB_CF_Cache_Purge_Post_Object constructor.
     * @param $post_id
     */
    public function __construct($post_id) {
        parent::__construct($post_id);
    }

    /**
     * Get the post URL.
     *
     * @return mixed
     */
    public function get_base_url() {
        return get_permalink( $this->get_id() );
    }

    /**
     * Get the post edit URL.
     *
     * @return mixed
     */
    public function get_edit_url() {
        return get_edit_post_link( $this->get_id() );
    }

    /**
     * Get the post title.
     *
     * @return mixed
     */
    public function get_title() {
        return get_the_title( $this->get_id() );
    }

    /**
     * Add URLs related to a post object.
     */
    protected function init_object() {

        // The URL to the post itself
        if ( $this->add_post_url() ) {
            $this->success(true); // Flag that found the post
            return true;
        } else {
            return false; // Could not find the post, stop execution
        }

    }

    /**
     * Generate URLs related to the object.
     */
    protected function generate_other_urls() {

        // The URL to the front page
        $this->add_front_page();

        // The URLs for categories, tags, post formats + any custom taxonomies for post type
        $this->add_taxonomy_archives();

        // The URL to the post type archive for the post type of the post
        $this->add_post_type_archive();

        // The URL to the author archive
        $this->add_author_archive();

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
        $post_permalink = $this->get_base_url();
        if ( $post_permalink && ! is_wp_error($post_permalink) ) {
            $this->add_url($post_permalink);
            return true;
        }
        return false;
    }

    /**
     *
     */
    private function add_post_type_archive() {
        $post_type_archive_url = get_post_type_archive_link($this->get_post_type());
        if ( $post_type_archive_url && ! is_wp_error($post_type_archive_url) ) {
            $this->add_urls(sb_paginate_links_as_array($post_type_archive_url, $this->get_pages_needed($post_type_archive_url)));
        }
    }

    /**
     * Add author URL to be purged.
     */
    private function add_author_archive() {
        $author = $this->get_post_author();
        if ( $author && ! is_wp_error($author) ) {
            $author_url = get_author_posts_url($author);
            if ( $author_url && ! is_wp_error($author_url) ) {
                $this->add_urls(sb_paginate_links_as_array($author_url, $this->get_pages_needed($author_url)));
            }
        }
    }

    /**
     *
     */
    private function add_taxonomy_archives() {
        $taxonomies = get_taxonomies( [ 'object_type' => [ $this->get_post_type() ] ] );
        if ( is_array($taxonomies) ) {
            foreach ( $taxonomies as $taxonomy_slug => $taxonomy_name ) {
                $terms = wp_get_post_terms($this->get_id(), $taxonomy_slug);
                if ( is_array($terms) ) {
                    foreach ( $terms as $term ) {
                        $term_link = get_term_link($term, $taxonomy_slug);
                        if ( $term_link && ! is_wp_error($term_link) ) {
                            $this->add_urls(sb_paginate_links_as_array($term_link, $this->get_pages_needed($term_link)));
                        }
                    }
                }
            }
        }
    }

    /**
     *
     */
    private function add_date_archive() {
        $year  = get_the_time('Y', $this->get_id());
        $month = get_the_time('m', $this->get_id());
        $day   = get_the_time('d', $this->get_id());
        $date_archive = get_day_link($year, $month, $day);
        if ( $date_archive && ! is_wp_error($date_archive) ) {
            $this->add_urls(sb_paginate_links_as_array($date_archive, $this->get_pages_needed($date_archive)));
        }
    }

    /**
     * Get the author of a post.
     *
     * @return mixed
     */
    private function get_post_author() {
        $post = get_post( $this->get_id() );
        return isset($post->post_author) ? $post->post_author : false;
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
