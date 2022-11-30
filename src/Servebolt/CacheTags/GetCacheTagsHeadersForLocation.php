<?php
namespace Servebolt\Optimizer\CacheTags;

use Servebolt\Optimizer\CacheTags\CacheTagsBase;
use \WP_Query;

class GetCacheTagsHeadersForLocation extends CacheTagsBase {

    /**
     * Post Type or Term ID
     * @var int
     */
    protected $objectId = 0;

    /**
     * Post type name or term
     * @var string 
     */
    protected $objectType = 'post';

    /**
     * @param int $objectId
     * @param string $objectType
     */
    public function __construct(int $objectId = 0, string $objectType = 'post')
    {
        // If post ID is not correctly set, then leave early
        if($objectId == 0) return;
        $this->objectId = $objectId;        
        $this->objectType = $objectType; // is this a post type or term?        
        $this->setupHeaders();        
    }

    protected function setupHeaders() : void
    {
        
        if($this->objectType == 'term') {
            $this->add('term-'.$this->objectId);
            return;
        } 

        $args = [
            'posts_per_page' => 1,
            'post_type' => $this->objectType,
            'p' => $this->objectId
        ];

        $loop = new WP_Query($args);
        if($loop->have_posts()) {
            while ( $loop->have_posts() ) : $loop->the_post();
                $this->getTagHeaders();
            endwhile;
        } else {
            error_log("post not found in CacheTag investigation loop");
        }
        wp_reset_postdata();
    }

    protected function getTagHeaders() : void
    {
        $this->addAuthorTag();
        $this->addHomeTag();
        $this->addTaxonomyTermIDTag();
        $this->addDateTag();
        $this->addRssTag();
        $this->addPostTypeTag();                
        $this->addWooCommerceTag();
    }

    /**
     * Clear the homepage and front page.
     */
    protected function addHomeTag() : void
    {
        $this->add('home');
    }

    /**
     * Clear the Feeds for both the comment RSS and general Feed.
     */
    protected function addRssTag() : void
    {
        $this->add('comment-feed' . get_the_ID());
        $this->add('feed');
    }

    /**
     * Clear the post type archive. 
     */
    protected function addPostTypeTag(): void
    {
        $this->add('posttype-'.get_post_type());
    }

    /**
     * clear date archives for the day month and year.
     */
    protected function addDateTag(): void
    {
        $this->add('date-'. get_the_date('n') .'-' . get_the_date('Y'));
        $this->add('year-'.  get_the_date('Y'));
        $this->add('month-'. get_the_date('n'));
    }

    /**
     * Add all terms for this post or page.
     */
    protected function addTaxonomyTermIDTag(): void
    {
        $taxonomies = get_object_taxonomies( $this->objectType, 'objects' );
        foreach($taxonomies as $tax) {
            // ignore non public taxonomies
            if(!$tax->public) continue;
            $ids = wp_get_post_terms(get_the_ID(), $tax->name, ['fields' => 'ids']);
            // ignore empty taxonomies or ignore error and continue;
            if(count($ids) == 0 || is_wp_error($ids)) continue;
            // loop all ids and add them
            foreach($ids as $id) {
                $this->add('term-'.$id);
            }
        } 
    }
    
    /**
     * Add author id to single pages and author archive pages
     * for Cache-Tag headers.
     * 
     * domain-author-[author id].
     */
    protected function addAuthorTag() : void
    {
        $this->add('author-' . get_post_field('post_author', get_the_ID() ) );
    }

    /**
     * If a WooCommerce product, clear the shop cache
     */
    protected function addWooCommerceTag() : void
    {
        // check if woo commerce is working on this site.
        if ( !class_exists( 'woocommerce' ) ) return;
        // Add the shop homepage.
        $this->add('woocommerce-shop');
    }

}
