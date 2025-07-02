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
use function Servebolt\Optimizer\Helpers\smartGetOption;

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
class AddCacheTagsHeaders extends CacheTagsBase
{

    // Use the Multiton trait to allow for singleton
    use Multiton;

    /**
     * CachePurge constructor.
     * @param int|null $blogId
     */
    public function __construct(?int $blogId = null)
    {

        $this->driver = self::getSelectedCachePurgeDriver($blogId);

        if ($this->driver == 'serveboltcdn') {
            add_filter('sb_optimizer_admin_bar_cache_purge_can_purge_url', '__return_false');
            add_filter('sb_optimizer_allow_admin_bar_cache_purge_for_term', '__return_false');
            add_filter('sb_optimizer_can_purge_term_cache', '__return_false');
        }

        if (
            is_admin()
            || isAjax()
            || isCron()
            || isCli()
            || isWpRest()
            || isTesting()
        ) return;

        if (in_array($this->driver, CanUseCacheTags::allowedDrivers())) {
            if ($this->driver == 'cloudflare') {
                if (smartGetOption($blogId, 'cache_purge_switch', smartGetOption($blogId, 'cf_switch')) !== '1') {
                    return;
                }

                if (smartGetOption($blogId, 'cf_cache_tags', '1') !== '1') {
                    return;
                }
            }

            // Get the correct hook based on version of WordPress, pre 6.1 wp, post send_headers.
            add_action(getCondtionalHookPreHeaders(), [$this, 'addCacheTagsHeaders']);
        }
    }

    /**
     * Works out what cache tage headers are needed in the header CacheTag for the current location.
     */
    public function addCacheTagsHeaders()
    {
        $this->setPrefixAndSuffixForTags();
        // Filter allows customer to use reduced instruction set for CacheTags.
        // If filter returns false, an Accelerated Domains customer will use the Servebolt CDN cache tags.
        if ($this->driver != 'serveboltcdn' && apply_filters('sb_optimizer_cache_tags_fine_grain_control', true)) {
            $this->addAuthorTag();
            $this->addTaxonomyTermIDTag();
            $this->addDateTag();
            $this->addRssTag();
            $this->addPostTypeTag();
            $this->addHomeTag();
            $this->addWooCommerceTag();
            $this->addSearch();
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
    protected function appendHeaders(): void
    {

        // Exit early if possible
        if (is_user_logged_in()) return;
        if (count($this->headers) == 0) return;

        // Add the headers to the HTTP headers
        if (count($this->headers) > 0 && !headers_sent()) {
            try {
                $tags = implode(',', $this->headers);
                header('Cache-Tag: ' . $tags);
                if ($this->driver == 'acd') {
                    header('x-acd-Cache-Tag: ' . $tags);
                }
            } catch (Exception $e) {
                error_log("Cache-Tag messages could not be added to the headers. Error: " . $e->getMessage());
            }
        } else {
            error_log("Cache-Tag headers could not be sent. Headers already have been sent. Cache purging is effected, contact Servebolt Support for help.");
        }
    }
}
