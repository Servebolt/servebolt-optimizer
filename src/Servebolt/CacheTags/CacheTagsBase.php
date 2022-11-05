<?php
namespace Servebolt\Optimizer\CacheTags;


class CacheTagsBase {

    /**
     * Array to hold headers that might be added to the page
     * 
     * @var array
     */
    protected $headers = [];

    /**
     * Set a domain prefix for tags
     *
     * @var string
     */
    protected $domain = '';

    /**
     * Option name used to store the cache tags
     * 
     * @vart
     */
    protected $cache_tags_status = 'added_cache_tags';
    /**
     * Add post type to Cache-Tag header where possible
     * 
     * domain-posttype-[posttype name]
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
     * domain-term-[term id]
     */
    protected function addTaxonomyTermIDTag() : void
    {
        if(is_category() || is_tag() || is_tax() ) {            
            $this->add('term-'. get_queried_object_id());
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
                }
            }            
        }
    }

    /**
     * Add author id to single pages and author archive pages
     * for Cache-Tag headers
     * 
     * domain-author-[author id]
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
     * domain-date-[month number]-[year number]
     * domain-year-[year number]
     * domain-month-[month number]
     * 
     */
    protected function addDateTag() : void
    {
        if(is_date()) {
            $this->add('date-'. get_query_var('monthnum') .'-' . get_query_var('year'));
            $this->add('year-'.  get_query_var('year'));
            $this->add('month-'. get_query_var('monthnum'));
        }

        if(is_singular() && !is_home() && !is_front_page()) {
            $this->add('date-'. get_the_date('n') .'-' . get_the_date('Y'));
            $this->add('year-'.  get_the_date('Y'));
            $this->add('month-'. get_the_date('n'));
        }
    }

    /**
     * If a rss feed add the Cache-Tag
     * 
     * domain-feed
     * 
     * If a post comment feed add the Cache-Tag
     * 
     * domain-comment-feed     
     * 
     */
    protected function addRssTag() : void
    {
        if(is_feed() && !is_singular()) {
            $this->add('feed');
        }
        
        if(is_feed() && is_singular()) {
            $this->add('comment-feed');
        }
    }

    /**
     * TODO: add sitemap cache-tag.  Might be too complex as it would need
     * to have deal with most popular SEO/Sitemap plugins
     */
    protected function addSitemapTag() : void
    {
        $this->add('sitemap');
    }

    /**
     * If the homepage or the main blog page
     * 
     * domain-home
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
        // domain-woocommerce
        if(is_woocommerce()) {
            $this->add('woocommerce');
        }
        // is the main shop page, normally /shop
        // domain-woocommerce-shop
        if(is_shop()) {
            $this->add('woocommerce-shop');
        }
        // is a product category archive page of woocommerce
        // domain-woocommerce-category
        if(is_product_category()){
            $this->add('woocommerce-category');
        }
        // is a product tag archive page of woocommerce
        // domain-woocommerce-tag
        if(is_product_tag()){
            $this->add('woocommerce-tag');
        }
        // is a product page of woocommerce
        // domain-woocommerce-product
        if(is_product()){
            $this->add('woocommerce-product');
        }        
    }

    protected function add( $name ) : void
    {
        $prefix = ($this->domain == '') ? $this->domain : $this->domain .'-';
        $this->headers[] = $prefix.$name;
    }

    protected function setupDomain() : void
    {
        $this->domain = str_replace('.', '', parse_url(home_url(), PHP_URL_HOST));
    }

}
