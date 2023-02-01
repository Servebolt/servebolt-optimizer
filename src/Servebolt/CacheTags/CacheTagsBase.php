<?php
namespace Servebolt\Optimizer\CacheTags;

use function \Servebolt\Optimizer\Helpers\getDomainNameOfWebSite;
class CacheTagsBase {

    /**
     * Array to hold headers that might be added to the page
     * 
     * @var array
     */
    protected $headers = [];

    /**
     * Set a blog suffix for tags.
     *
     * @var string
     */
    protected $blogId = '';

    /**
     * Set a domain prefix for tags.
     *
     * @var string
     */
    protected $domain = '';

    /**
     * Set a addionalPrefix for tags.
     *
     * @var string
     */
    protected $additionalPrefix = '';

    /**
     * Separator style
     * 
     * @var string
     */
    protected $separator = '-';
    /**
     * Driver
     * 
     * @var string
     */
    protected $driver = '';
    /**
     * Option name used to store the cache tags
     * 
     * @var
     */
    protected $cache_tags_status = 'added_cache_tags';

    protected function setPrefixAndSuffixForTags()
    {
        $this->setBlog();
        $this->setDomain();
        /**
         * @param string hook for filter sb_optimizer_cachetags_additional_prefix
         * @param string current state of additional prefix
         * @param string the domain value without dots
         * @param string the possible blog id if on multisite
         */
        $this->additionalPrefix = apply_filters('sb_optimizer_cachetags_additional_prefix', $this->additionalPrefix, $this->domain, $this->blogId);
    }

    protected function setDomain()
    {
        $this->domain = str_replace('.','',getDomainNameOfWebSite());
    }

    protected function setBlog()
    {
        if (is_multisite()) {
            $this->blogId = get_current_blog_id();
        }
    }
    /**
     * Add post type to Cache-Tag header where possible
     * 
     * posttype-[posttype name]
     */
    protected function addPostTypeTag() : void
    {
        if(is_post_type_archive()) {
            $this->add('posttype-'.get_queried_object()->name );
        }
        if(is_singular()) {
            $this->add('posttype-'.get_post_type());
        }
    }

    /**
     * Add taxanomy ids to single pages or archive pages
     * for Cache-Tag headers
     * 
     * term-[term id]
     */
    protected function addTaxonomyTermIDTag() : void
    {
        if(is_category() || is_tag() || is_tax() ) {            
            $this->add('term-'. get_queried_object_id());
            // TODO: decide how much effort to put into RSS
            // $this->add('term-feed-'.get_queried_object_id());
        }

        if(is_singular()) {
            $taxonomies = get_object_taxonomies( get_post_type(), 'objects' );
            foreach($taxonomies as $tax) {
                // ignore non public taxonomies
                if(!$tax->public) continue;
                $ids = wp_get_post_terms(get_queried_object()->ID, $tax->name, ['fields' => 'ids']);
                // ignore empty taxonomies or ignore error and continue;
                if(count($ids) == 0 || is_wp_error($ids)) continue;
                // loop all ids and add them
                foreach($ids as $id) {
                    $this->add('term-'.$id);
                    // TODO: decide how much effort to put into RSS
                    //$this->add('term-feed-'.$id);
                }
            }            
        }
    }

    /**
     * Add author id to single pages and author archive pages
     * for Cache-Tag headers
     * 
     * author-[author id]
     */
    protected function addAuthorTag() : void
    {
        
        if(is_author()){
            $this->add('author-' . get_the_author_meta('ID') );
        }

        if(is_singular()){
            $this->add('author-' . get_post_field('post_author', get_queried_object()->ID ) );
        }

    }

    /**
     * If a data archive add or Single Page
     * 
     * date-[month number]-[year number]
     * year-[year number]
     * month-[month number]
     * 
     */
    protected function addDateTag() : void
    {
        if(is_date()) {
            $this->add('date-'.get_query_var('day') .'-'. get_query_var('monthnum') .'-'. get_query_var('year'));
            $this->add('year-'.  get_query_var('year'));
            $this->add('month-'. get_query_var('monthnum'));
        }

        if(is_singular() && !is_home() && !is_front_page()) {
            $this->add('date-'.get_the_date('d-n-Y'));
            $this->add('year-'.  get_the_date('Y'));
            $this->add('month-'. get_the_date('n'));
        }
    }

    /**
     * If a rss feed add the Cache-Tag
     * 
     * feed
     * 
     * If a post comment feed add the Cache-Tag
     * 
     * comment-feed     
     * 
     */
    protected function addRssTag() : void
    {
        if(is_feed() && !is_singular()) {
            $this->add('feeds');
        }
        
        if(is_feed() && is_singular()) {
            $this->add('comment-feed' . get_queried_object()->ID);
        }
    }

    /**
     * 
     * TODO: add sitemap cache-tag.  Might be too complex as it would need
     * to have deal with most popular SEO/Sitemap plugins.
     *
     **/
    protected function addSitemapTag() : void
    {
        $this->add('sitemap');
    }

    /**
     * If the homepage or the main blog page.
     *
     */
    protected function addHomeTag() : void
    {
        if(is_home()||is_front_page()) {
            $this->add('home');
        }
    }

    /**
     * Add Cache-Tag to woocommerce pages
     */
    protected function addWooCommerceTag() : void
    {
        // check if woo commerce is working on this site
        if ( !class_exists( 'woocommerce' ) ) return;

        // is this page generated with woocommerce
        // woocommerce
        if(is_woocommerce()) {
            $this->add('woocommerce');
        }
        // is the main shop page, normally /shop
        // woocommerce-shop
        if(is_shop()) {
            $this->add('woocommerce-shop');
        }
        // is a product category archive page of woocommerce
        // woocommerce-category
        if(is_product_category()){
            $this->add('woocommerce-category');
        }
        // is a product tag archive page of woocommerce
        // woocommerce-tag
        if(is_product_tag()){
            $this->add('woocommerce-tag');
        }
        // is a product page of woocommerce
        // woocommerce-product
        if(is_product()){
            $this->add('woocommerce-product');
        }        
    }

    /**
     * Add a CacheTag.
     *
     * @return void
     */
    public function add( $name ) : void
    {
        // create tag from name i.e. domain-term-1
        $tag = $this->domain . $this->separator . $name;
        // append blog id when multisite i.e. domain-term-1-1
        $tag .= ($this->blogId != '') ? $this->separator . $this->blogId : '';
        // prepend addititional suffix if filter has been used. i.e. domain-term-1-1
        $tag = ($this->additionalPrefix != '') ? $this->additionalPrefix . $this->separator . $tag : $tag;
        $this->headers[] = $tag;
    }

    /**
     * Return the array of cachetag headers.
     *
     * @return array
     */
    public function getHeaders() : array
    {
        return $this->headers;
    }
}
