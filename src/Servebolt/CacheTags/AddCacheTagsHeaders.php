<?php

namespace Servebolt\Optimizer\CacheTags;

use Exception;
use Servebolt\Optimizer\Traits\Multiton;
use Servebolt\Optimizer\CacheTags\CacheTagsBase;
use function Servebolt\Optimizer\Helpers\isHostedAtServebolt;
use function Servebolt\Optimizer\Helpers\smartGetOption;
use function Servebolt\Optimizer\Helpers\isAjax;
use function Servebolt\Optimizer\Helpers\isCli;
use function Servebolt\Optimizer\Helpers\isCron;
use function Servebolt\Optimizer\Helpers\isTesting;
use function Servebolt\Optimizer\Helpers\isWpRest;
use function Servebolt\Optimizer\Helpers\smartAddOrUpdateOption;
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
        error_log('trying');
        if (
            is_admin()
            || isAjax()
            || isCron()
            || isCli()
            || isWpRest()
            || isTesting()
        ) return;

        
        $this->driver = self::getSelectedCachePurgeDriver($blogId);

       // if($this->driver == 'acd') {
            //
            // Get the correct hook based on version of WordPress, pre 6.1 wp, post send_headers
            add_action(getCondtionalHookPreHeaders(), [$this,'addCacheTagsHeaders']);
       // }
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

        $this->appendHeaders();
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
        // saving an (site) option that will be used if the page is purged so that 
        // the system will know to use a cache tag or urls for purging.
        smartAddOrUpdateOption( null, $this->cache_tags_status, $success);        
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