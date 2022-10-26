<?php

namespace Servebolt\Optimizer\CacheTags;

use Exception;
use Servebolt\Optimizer\Traits\Multiton;
use function Servebolt\Optimizer\Helpers\isHostedAtServebolt;
use function Servebolt\Optimizer\Helpers\smartGetOption;
use function Servebolt\Optimizer\Helpers\isAjax;
use function Servebolt\Optimizer\Helpers\isCli;
use function Servebolt\Optimizer\Helpers\isCron;
use function Servebolt\Optimizer\Helpers\isTesting;
use function Servebolt\Optimizer\Helpers\isWpRest;
use function Servebolt\Optimizer\Helpers\smartAddOrUpdateOption;
/**
 * This class adds cache-tags headers to the HTTP pages of the site. This is then
 * used by Cloudflare on Enterprize sites to allow for purging of a lot of pages in 
 * one go. It means that there is a singular request to have a group of pages purged in one go. 
 * 
 * Cache-tag types
 * - Home : homepage and frontpage of WordPress
 * - (custom) Post Type : purge all registered public singular post types (including attachments)
 * - (custom) taxonomy term id : purge all pagination pages of a taxonomy term
 * - Date based pages : month and year pages
 * - sitemaps : 
 * - author :
 * - RSS feeds :
 * 
 * 
 * Cache-Tag syntax
 * 
 * domain-type-value
 * 
 * i.e. https://www.servebolt.com/category/wordpress
 * 
 * Cache-Tag : wwwserveboltcom-term-11
 * 
 * @since 3.5.9
 * @author Andrew Killen
 */
class AddCacheTagsHeaders {

    // Use the Multiton trait to allow for singleton
    use Multiton;

    /**
     * Array to hold headers that might be added to the page
     * 
     * @var array
     */
    protected $headers = [];

    /**
     * Cache purge driver instance.
     *
     * @var mixed
     */
    private $driver;

    /**
     * Set a domain prefix for tags
     *
     * @var string
     */
    private $domain = '';

    /**
     * Drivers that require the site to be hosted at Servebolt.
     *
     * @var string[]
     */
    private static $serveboltOnlyDrivers = ['acd', 'serveboltcdn'];

    /**
     * Valid drivers.
     *
     * @var string[]
     */
    private static $validDrivers = ['cloudflare', 'acd', 'serveboltcdn'];

    /**
     * CachePurge constructor.
     * @param int|null $blogId
     */
    public function __construct(?int $blogId = null)
    {
        
        if (
            is_admin()
            || isAjax()
            || isCron()
            || isCli()
            || isWpRest()
            || isTesting()
        ) return;

        
        $this->driver = self::getSelectedCachePurgeDriver($blogId);


        if($this->driver != 'cloudflare') {

            $this->domain = str_replace('.', '', parse_url(home_url(), PHP_URL_HOST));
            // send_headers was not possible to use as the query object was not yet
            // created. Thus none of the conditional functions would work without 
            // hammering the db for loads of extra information.
            add_action( 'wp', [$this,'addCacheTagsHeaders'] );            
        }

    }

    /**
     * 
     * 
     */
    public function addCacheTagsHeaders()
    {
        $this->addAuthorTag();
        $this->addTaxonomyTermIDTag();
        $this->addDateTag();
        $this->addRssTag();
        $this->addPostTypeTag();        
        $this->addHomeTag();
        $this->addWooCommerceTag();
        // TODO: consider adding sitemap purging, but not thinking its needed.
        // $this->addSitemapTag();

        $this->appendHeaders();
    }

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
        // is cart page of woocommerce
        // domain-woocommerce-cart
        // TODO: work out if this is ever needed?
            // if(is_cart()){
            //     $this->add('woocommerce-cart');
            // }
        // is checkout page of woocommerce
        // domain-woocommerce-checkout
        // TODO: work out if this is ever needed?
            // if(is_checkout()){
            //     $this->add('woocommerce-checkout');
            // }
    }

    /**
     * Converts the $this->headers attay into a HTTP header
     * that is added after processing using the 'wp' hook
     */
    protected function appendHeaders() : void 
    {
        $success = true;
        if(count($this->headers) > 0) {
            try{
                header('Cache-Tag: ' . implode(', ', $this->headers));
            } catch (Exception $e){
                error_log("Cache-Tag messages could not be added as headers have already been sent.");
                $success = false;
            }
        }
        // saving a site option that will be used if the page is purged so that 
        // the system will know to use a cache tag or urls for purging.
        smartAddOrUpdateOption( null, 'added_cache_tags', $success);        
    }

    protected function add( $name ) : void
    {
        $this->headers[] = $this->domain.'-'.$name;
    }
    /**
     * Get default driver name.
     *
     * @param bool $verbose
     * @return string
     */
    private static function defaultDriverName(bool $verbose = false): string
    {
        return $verbose ? 'Cloudflare' : 'cloudflare';
    }

    /**
     * Get the selected cache purge driver.
     *
     * @param int|null $blogId
     * @param bool $strict
     * @return string
     */
    public static function getSelectedCachePurgeDriver(?int $blogId = null, bool $strict = true)
    {
        $defaultDriver = self::defaultDriverName();
        $driver = (string) apply_filters(
            'sb_optimizer_selected_cache_purge_driver',
            smartGetOption(
                $blogId,
                'cache_purge_driver',
                $defaultDriver
            )
        );
        if (!in_array($driver, self::$validDrivers)) {
            $driver = $defaultDriver;
        } else if ($strict && !isHostedAtServebolt() && in_array($driver, self::$serveboltOnlyDrivers)) {
            $driver = $defaultDriver;
        }
        return $driver;
    }

   
}