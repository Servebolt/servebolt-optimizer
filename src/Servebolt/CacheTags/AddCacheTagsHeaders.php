<?php

namespace Servebolt\Optimizer\CacheTags;

use Exception;
use Servebolt\Optimizer\Traits\Multiton;
use Servebolt\Optimizer\CacheTags\CacheTagsBase;
use Servebolt\Optimizer\CacheTags\CanUseCacheTags;
use function Servebolt\Optimizer\Helpers\isAjax;
use function Servebolt\Optimizer\Helpers\isCli;
use function Servebolt\Optimizer\Helpers\isCron;
use function Servebolt\Optimizer\Helpers\isTesting;
use function Servebolt\Optimizer\Helpers\isWpRest;
use function Servebolt\Optimizer\Helpers\getCondtionalHookPreHeaders;

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
class AddCacheTagsHeaders extends CacheTagsBase {

    // Use the Multiton trait to allow for singleton
    use Multiton;

    // /**
    //  * Drivers that require the site to be hosted at Servebolt.
    //  *
    //  * @var string[]
    //  */
    // private static $serveboltOnlyDrivers = ['acd', 'serveboltcdn'];

    // /**
    //  * Valid drivers.
    //  *
    //  * @var string[]
    //  */
    // private static $validDrivers = ['cloudflare', 'acd', 'serveboltcdn'];

    /**
     * CachePurge constructor.
     * @param int|null $blogId
     */
    public function __construct(?int $blogId = null)
    {   
       
        $this->driver = self::getSelectedCachePurgeDriver($blogId);

        if($this->driver == 'serveboltcdn') {
            add_filter('sb_optimizer_admin_bar_cache_purge_can_purge_url', '__return_false');
        }
        
        if (
            is_admin()
            || isAjax()
            || isCron()
            || isCli()
            || isWpRest()
            || isTesting()
        ) return;

        
        
        if(in_array($this->driver, CanUseCacheTags::allowedDrivers())) {
            // Get the correct hook based on version of WordPress, pre 6.1 wp, post send_headers.
            add_action(getCondtionalHookPreHeaders(), [$this,'addCacheTagsHeaders']);
        }
        
    }

    /**
     * 
     * 
     */
    public function addCacheTagsHeaders()
    {
        $this->setPrefixAndSuffixForTags();   
        if($this->driver != 'serveboltcdn') {
            $this->addAuthorTag();
            $this->addTaxonomyTermIDTag();
            $this->addDateTag();
            $this->addRssTag();
            $this->addPostTypeTag();        
            $this->addHomeTag();
            $this->addWooCommerceTag();
        } else {
            // All Servebolt CDN HTML/RSS pages come under addHTMLTag
            $this->addHTMLTag();
        }
        $this->appendHeaders();
    }

    
    /**
     * Converts the $this->headers attay into a HTTP header
     * that is added after processing using the 'wp' hook
     */
    protected function appendHeaders() : void 
    {
        if(count($this->headers) > 0) {
            try{
                $tags = implode(',', $this->headers);
                header('Cache-Tag: ' . $tags );
                if($this->driver == 'acd') {
                    header('x-acd-Cache-Tag: ' . $tags);
                } else {
                    header('X-CDN-Cache-Tag: ' . $tags);
                }
            }
            catch (Exception $e){
                error_log("Cache-Tag messages could not be added as headers have already been sent.");
            }
        } 
    }

   
}