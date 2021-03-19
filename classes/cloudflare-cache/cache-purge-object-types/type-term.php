<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

use function Servebolt\Optimizer\Helpers\paginateLinksAsArray;

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
     * @param $args
     */
    public function __construct($term_id, $args) {
        parent::__construct($term_id, $args);
    }

    /**
     * Get the term URL.
     *
     * @return mixed
     */
    public function get_base_url() {
        return get_term_link( $this->get_id() );
    }

    /**
     * Get the term edit URL.
     *
     * @return mixed
     */
    public function get_edit_url() {
        return get_edit_term_link( $this->get_id() );
    }

    /**
     * Get the term title.
     *
     * @return mixed
     */
    public function get_title() {
        $term = get_term( $this->get_id() );
        if ( isset($term->name) ) {
            return $term->name;
        }
        return false;
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
        $term_url = $this->get_base_url();
        if ( $term_url && ! is_wp_error($term_url) ) {
            $pages_needed = $this->get_pages_needed([
                'tax_query' => [
                    [
                        'taxonomy' => $this->get_argument('taxonomy_slug'),
                        'field'    => 'term_id',
                        'terms'    => $this->get_id(),
                    ]
                ],
            ], 'term');
            $this->add_urls(paginateLinksAsArray($term_url, $pages_needed));
            return true;
        }
        return false;
    }
}
