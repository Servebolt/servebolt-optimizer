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
        if($loop->have_posts()) :
            while ( $loop->have_posts() ) : $loop->the_post();
                $this->getTagHeaders();
            endwhile;
        endif;
        wp_reset_postdata();
    }

    protected function getTagHeaders() : void
    {
        $this->addAuthorTag();
        $this->addTaxonomyTermIDTag();
        $this->addDateTag();
        $this->addRssTag();
        $this->addPostTypeTag();        
        $this->addHomeTag();
        $this->addWooCommerceTag();
    }

    public function getHeaders() : array
    {
        return $this->headers;
    }
    
}
