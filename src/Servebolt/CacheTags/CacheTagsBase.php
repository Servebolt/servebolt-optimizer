<?php
namespace Servebolt\Optimizer\CacheTags;

use Servebolt\Optimizer\Api\Servebolt\Servebolt;
use function \Servebolt\Optimizer\Helpers\getDomainNameOfWebSite;
use function Servebolt\Optimizer\Helpers\isHostedAtServebolt;
use function Servebolt\Optimizer\Helpers\smartGetOption;

class CacheTagsBase {
    /**
     * Class constants are used to convert labels (const names) into numbers.
     * This way they human readable in the code, but machine readable as a cachetag
     *
     * These are grouped
     * 0x = global
     * 1x = post type
     * 2x = taxonomy
     * 3x = feeds
     * 4x = woocommerce
     *
     */

    // Global
    // Leading zero is lost when used as an int. 
    const HOME = '00';
    const HTML = '01';
    const SITEMAP = '03'; // Not currently used
    const SEARCH = '04'; // Not currently used
    // Post Type
    const POST_TYPE = 10;
    const AUTHOR = 11;
    const DATE = 12;
    const MONTH = 13;
    const YEAR = 14;
    
    

    // Taxonomy
    const TERM_ID = 20;
    const TAXONOMY_ID = 21; // not currently used.

    // Feeds
    const FEEDS = 30;
    const COMMENT_FEED = 31;

    // WooCommerce
    const WOOCOMMERCE = 40;
    const WOOCOMMERCE_SHOP = 41;
    const WOOCOMMERCE_CATEGORY = 42;
    const WOOCOMMERCE_TAG = 43;
    const WOOCOMMERCE_PRODUCT = 44;
    const WOOCOMMERCE_PRODUCT_ID = 45;

    /**
     * Drivers that require the site to be hosted at Servebolt.
     *
     * @var string[]
     */
    protected static $serveboltOnlyDrivers = ['acd', 'serveboltcdn'];

    /**
     * Valid drivers.
     *
     * @var string[]
     */
    protected static $validDrivers = ['cloudflare', 'acd', 'serveboltcdn'];

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

    /**
     * Choose the shortest unique identifier. 
     * 
     * 1. the domain name without dots
     * 2. the servebolt environment ID and bolt ID combined. 
     */
    protected function setDomain()
    {
        $environment_file = Servebolt::getInstance();
        $combined_id = $environment_file->getEnvironmentId() . $environment_file->getBoltId();
        $domain = str_replace('.','',getDomainNameOfWebSite());
        $this->domain = ( strlen($domain) >= strlen($combined_id) ) ? $domain : $combined_id;
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
            $this->add(self::POST_TYPE.'-'.get_queried_object()->name );
        }
        if(is_singular()) {
            $this->add(self::POST_TYPE.'-'.get_post_type());
        }
    }
    /**
     * Add 'html' tag to every possible page, its used by Servebolt CDN.
     * 
     */
    protected function addHTMLTag() : void
    {
        $this->add(self::HTML);
    }
    /**
     * Add search tag when on a search page
     */
    protected function addSearch() : void
    {
        if(is_search()) {
            $this->add(self::SEARCH);
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
            $this->add(self::TERM_ID . '-'. get_queried_object_id());
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
                if( is_wp_error($ids) || count($ids) == 0 ) continue;

                foreach($ids as $id) {
                    $this->add(self::TERM_ID . '-'.$id);
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
            $this->add( self::AUTHOR . '-' . get_the_author_meta('ID') );
        }

        if(is_singular()){
            if(class_exists( 'woocommerce' ) && is_product()) return;

            $this->add( self::AUTHOR . '-' . get_post_field('post_author', get_queried_object()->ID ) );
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
            // $this->add(self::DATE .'-' .get_query_var('day') .'-'. get_query_var('monthnum') .'-'. get_query_var('year'));
            // $this->add(self::YEAR .'-'.  get_query_var('year'));
            // $this->add(self::MONTH .'-'. get_query_var('monthnum'));
            $this->add(self::DATE);
        }

        if(is_singular() && !is_home() && !is_front_page()) {
            if(is_page() || (class_exists( 'woocommerce' ) && is_product() ) ) return;
            // $this->add(self::DATE .'-' .get_the_date('d-n-Y'));
            // $this->add(self::YEAR .'-'.  get_the_date('Y'));
            // $this->add(self::MONTH .'-'. get_the_date('n'));
            $this->add(self::DATE);
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
            $this->add(self::FEEDS);
        }
        
        if(is_feed() && is_singular()) {
            $this->add(self::COMMENT_FEED. '-' . get_queried_object()->ID);
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
        $this->add(self::SITEMAP);
    }

    /**
     * If the homepage or the main blog page.
     *
     */
    protected function addHomeTag() : void
    {
        if(is_home()||is_front_page()) {
            $this->add(self::HOME);
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
            $this->add(self::WOOCOMMERCE);
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
