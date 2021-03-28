<?php

namespace Servebolt\Optimizer\FullPageCache;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Admin\GeneralSettings\GeneralSettings;
use Servebolt\Optimizer\Traits\Singleton;
use function Servebolt\Optimizer\Helpers\checkboxIsChecked;
use function Servebolt\Optimizer\Helpers\isAjax;
use function Servebolt\Optimizer\Helpers\formatArrayToCsv;
use function Servebolt\Optimizer\Helpers\updateBlogOption;
use function Servebolt\Optimizer\Helpers\getBlogOption;
use function Servebolt\Optimizer\Helpers\updateOption;
use function Servebolt\Optimizer\Helpers\getOption;
use function Servebolt\Optimizer\Helpers\fullPageCache;
use function Servebolt\Optimizer\Helpers\writeLog;

/**
 * Class FullPageCache
 * @package Servebolt
 *
 * This class handles the actual cache handling by passing headers from WordPress/Apache to the Nginx web server.
 * Note: Only relevant for websites hosted at Servebolt!
 */
class FullPageCache
{
    use Singleton;

	/**
	 * The post types that are cacheable.
	 *
	 * @var array
	 */
	private $postTypes = [];

	/**
	 * Whether headers are already set for this request.
	 *
	 * @var bool
	 */
	private $headersAlreadySet = false;

	/**
	 * Cache the post ids to exclude from cache.
	 *
	 * @var null
	 */
	private $idsToExcludeCache = null;

	/**
	 * The default browser cache time.
	 *
	 * @var int
	 */
	private $browserCacheTime = 600;

	/**
	 * The default Full Page Cache time.
	 *
	 * @var int
	 */
	private $fpcCacheTime = 600;

	/**
	 * Whether we should attempt to set headers even tho headers are already sent (not good practice, should be fixed).
	 *
	 * @var bool
	 */
	private $allowForceHeaders = false;

    /**
     * Whether to use the Cloudflare APO-feature.
     *
     * @var bool
     */
    private $cfApoActive = null;

    public static function init(): void
    {
        self::getInstance();
    }

	/**
	 * FullPageCache constructor.
	 */
	private function __construct()
    {

		// Handle "no_cache"-header for authenticated users.
        FullPageCacheAuthHandling::init();

		// Unauthenticated cache handling
		add_filter('posts_results', [$this, 'setHeaders']);
		add_filter('template_include', [$this, 'lastCall']);

	}

	/**
	 * Check if full page caching is active with optional blog check.
	 *
	 * @param bool|int $blogId
	 *
	 * @return bool
	 */
	public function fpcIsActive($blogId = false)
    {
		if (is_numeric($blogId)) {
			return checkboxIsChecked(getBlogOption($blogId, $this->fpcActiveOptionKey()));
		} else {
			return checkboxIsChecked(getOption($this->fpcActiveOptionKey()));
		}
	}

	/**
	 * Set cache headers - Determine and set the type of headers to be used.
	 *
	 * @param $posts
	 *
	 * @return mixed
	 */
	public function setHeaders($posts)
    {

		$debug = $this->shouldDebug();

		// Abort if cache headers are already set
		if ($this->headersAlreadySet) {
            return $posts;
        }
		$this->headersAlreadySet = true;

        // No cache if FPC is not active, or if we are logged in
        if (!$this->fpcIsActive() || is_admin() || isAjax() || is_user_logged_in()) {
            $this->noCacheHeaders();
            if ($debug) {
                $this->header('No-cache-trigger: 1');
            }
            return $posts;
        }

		global $wp_query;
		$postType = get_post_type();

		// We don't have any posts at the time, abort
		if (!isset($wp_query) || !$postType) {
			$this->headersAlreadySet = false;
			return $posts;
		}

		// Only trigger this function once
		remove_filter('posts_results', [$this, 'setHeaders']);

		if ($this->isWoocommerceNoCachePage()) {
			$this->noCacheHeaders();
			if ($debug) {
                $this->header('No-cache-trigger: 2');
            }
		} elseif ($this->isWoocommerceCachePage()) {
			$this->cacheHeaders();
			if ($debug) {
                $this->header('Cache-trigger: 11');
            }
		} elseif ($this->shouldExcludePostFromCache(get_the_ID())) {
			$this->noCacheHeaders();
			if ($debug) {
                $this->header('No-cache-trigger: 3');
            }
		} elseif ($this->cacheAllPostTypes()) {
			// Make sure the post type can be cached
			$this->postTypes[] = $postType;
			$this->cacheHeaders();
			if ($debug) {
                $this->header('Cache-trigger: 4');
            }
		} elseif ((is_front_page() || is_singular() || is_page()) && $this->cacheActiveForPostType($postType)) {
			// Make sure the post type can be cached
			$this->postTypes[] = $postType;
			$this->cacheHeaders();
			if ($debug) {
                $this->header('Cache-trigger: 5');
            }
		} elseif (is_archive() && $this->shouldCacheArchive($posts)) {
			// Make sure the archive has only cacheable posts
			$this->cacheHeaders();
			if ($debug) {
                $this->header('Cache-trigger: 6');
            }
		} elseif (empty($this->getPostTypesToCache())) {
			$this->postTypes[] = $postType;
			if ($debug) {
                $this->header('Cache-trigger: 7');
            }
			if (in_array($postType , $this->getDefaultPostTypesToCache())) {
				$this->cacheHeaders();
				if ($debug) {
                    $this->header('Cache-trigger: 8');
                }
			}
		} elseif (empty($this->getPostTypesToCache()) && (is_front_page() || is_singular() || is_page())) {
			$this->cacheHeaders();
			if ($debug) {
                $this->header('Cache-trigger: 9');
            }
		} else {
			// Default to no-cache headers
			$this->noCacheHeaders();
			if ($debug) {
                $this->header('No-cache-trigger: 10');
            }
		}
		return $posts;
	}

	/**
	 * Whether we should debug headers or not.
	 *
	 * @return bool
	 */
	private function shouldDebug(): bool
    {
		return apply_filters('sb_optimizer_fpc_should_debug_headers', true);
	}

	/**
	 * Set a header by best effort.
	 *
	 * @param $string
	 */
	private function header($string)
    {

		// Abort if headers are already sent
		if (headers_sent() && ! $this->allowForceHeaders) {
            writeLog(sprintf('Servebolt Optimizer attempted to set header "%s", but headers already sent.', $string));
			return;
		}

		// WP action already passed but headers are not yet sent
		if (did_action('send_headers')) {
			header($string);
			return;
		}

		// Set headers using WP's "send_headers"-action
		add_action('send_headers', function () use ($string) {
			header($string);
		});
	}

	/**
	 * Last call - Run a last call to the set headers function before the template is loaded.
	 *
	 * @param $template
	 *
	 * @return mixed
	 */
	public function lastCall($template)
    {
		$this->setHeaders([get_post()]);
		return $template;
	}

	/**
	 * Check if we should exclude post from cache.
	 *
	 * @param $postId
	 *
	 * @return bool
	 */
	public function shouldExcludePostFromCache($postId)
    {
		$idsToExclude = $this->getIdsToExcludeFromCache();
		return is_array($idsToExclude) && in_array($postId, $idsToExclude);
	}

	/**
	 * Check if WooCommerce is active.
	 *
	 * @return bool
	 */
	private function woocommerceIsActive(): bool
    {
		return class_exists('WooCommerce');
	}

	/**
	 * Check if cache is active for given post type.
	 *
	 * @param $postType
	 *
	 * @return bool
	 */
	private function cacheActiveForPostType($postType): bool
    {
		if (in_array($postType, (array) $this->getPostTypesToCache())) {
			return true;
		}
		return false;
	}

	/**
	 * Check if cache is active for all post types.
	 *
	 * @return bool
	 */
	private function cacheAllPostTypes()
    {
		return $this->cacheActiveForPostType('all');
	}

	/**
	 * Check if we should cache an archive.
	 *
	 * @param  array $posts Posts in the archive
	 * @return boolean      Return true if all posts are cacheable
	 */
	private function shouldCacheArchive($posts): bool
    {
		foreach ($posts as $post) {
            if (!in_array($post->post_type, $this->getPostTypesToCache())) {
				return false;
			} elseif (!in_array($post->post_type, (array) $this->postTypes)) {
				$this->postTypes[] = $post->post_type;
			}
		}
		return true;
	}

    /**
     * Whether to use the Cloudflare APO-feature.
     *
     * @return bool
     */
	private function cfApoActive()
    {
	    if (is_null($this->cfApoActive)) {
            $generalSettings = GeneralSettings::getInstance();
            $this->cfApoActive = $generalSettings->useCloudflareApo();
        }
	    return $this->cfApoActive;
    }

	/**
	 * Cache headers - Print headers that encourage caching.
	 */
	private function cacheHeaders()
    {

        // Check if the constant SERVEBOLT_FPC_CACHE_TIME is set, and override $serveboltNginxCacheTime if it is
        if (defined('SERVEBOLT_FPC_CACHE_TIME')) {
	        $this->fpcCacheTime = SERVEBOLT_FPC_CACHE_TIME;
        }

        // Check if the constant SERVEBOLT_BROWSER_CACHE_TIME is set, and override $serveboltBrowserCacheTime if it is
        if (defined('SERVEBOLT_BROWSER_CACHE_TIME')) {
	        $this->browserCacheTime = SERVEBOLT_BROWSER_CACHE_TIME;
        }

        // Cloudflare APO-support
        if ($this->cfApoActive()) {
            $this->header( 'cf-edge-cache: cache, platform=wordpress' );
        }

		header_remove('Cache-Control');
		header_remove('Pragma');
		header_remove('Expires');

		// Allow browser to cache content for 10 minutes, or the set browser cache time constant
		$this->header(sprintf('Cache-Control: max-age=%s, public, s-maxage=%s', $this->browserCacheTime, $this->fpcCacheTime));
		$this->header('Pragma: public');

		// Expire in front-end caches and proxies after 10 minutes, or use the constant if defined.
		$expiryString = gmdate('D, d M Y H:i:s', $_SERVER['REQUEST_TIME'] + $this->fpcCacheTime) . ' GMT';
		$this->header(sprintf('Expires: %s', $expiryString));
		$this->header('X-Servebolt-Plugin: active');
	}

	/**
	 * No cache headers - Print headers that prevent caching.
	 */
	private function noCacheHeaders(): void
    {
		$this->header( 'Cache-Control: max-age=0,no-cache,s-maxage=0' );
		$this->header( 'Pragma: no-cache' );
		$this->header( 'X-Servebolt-Plugin: active' );

		// Cloudflare APO-support
        $this->header( 'cf-edge-cache: no-cache, platform=wordpress' );
	}

	/**
	 * The option name/key we use to store the cacheable post types for the Nginx FPC cache.
	 *
	 * @return string
	 */
	private function fpcCacheablePostTypesOptionKey(): string
    {
		return 'fpc_settings';
	}

	/**
	 * Set cacheable post types.
	 *
	 * @param $postTypes
	 * @param bool $blogId
	 *
	 * @return bool|mixed
	 */
	public function setCacheablePostTypes($postTypes, $blogId = false)
    {
		if (is_numeric($blogId)) {
			return updateBlogOption($blogId, $this->fpcCacheablePostTypesOptionKey(), $postTypes);
		} else {
			return updateOption($this->fpcCacheablePostTypesOptionKey(), $postTypes);
		}
	}

	/**
	 * Get post types that we can apply cache for.
	 *
	 * @param bool $includeAll
	 *
	 * @return mixed
	 */
	public function getAvailablePostTypesToCache($includeAll = false)
    {
        $postTypes = get_post_types(['public' => true], 'objects');
		$array = [];
		if ($includeAll) {
			$array['all'] = __('All', 'servebolt-wp');
		}
		foreach ($postTypes as $postType) {
			$array[$postType->name] = $postType->labels->singular_name;
		}
		return $array;
	}

	/**
	 * Get the post types to cache.
	 *
	 * @param bool $respectDefaultFallback Whether to fall back on default post types.

	 * @param bool $respectAll Whether to return all post types if all is selected.
	 * @param bool $blogId The ID of the blog we would like to interact with.
	 *
	 * @return array|mixed|string|void|null A list of cacheable post types.
	 */
	public function getPostTypesToCache($respectDefaultFallback = true, $respectAll = true, $blogId = false)
    {

		if (is_numeric($blogId)) {
			$postTypesToCache = getBlogOption($blogId, $this->fpcCacheablePostTypesOptionKey());
		} else {
            $postTypesToCache = getOption($this->fpcCacheablePostTypesOptionKey());
		}

		// Make sure we migrate from old array structure
        $postTypesToCache = $this->maybeFixPostTypeArrayStructure($postTypesToCache);

		if ($respectAll && in_array('all', (array) $postTypesToCache)) {
			return array_keys($this->getAvailablePostTypesToCache());
		}

		// Check if we should return default post types instead of not post types are set
		if ($respectDefaultFallback && (!is_array($postTypesToCache) || empty($postTypesToCache))) {
            $postTypesToCache = $this->getDefaultPostTypesToCache();
		}

		return $postTypesToCache;
	}

	/**
	 * Convert array structure from old to new (to keep backwards compatibility with 1.6.2<=).
	 *
	 * @param $array
	 *
	 * @return array
	 */
	private function maybeFixPostTypeArrayStructure($array)
    {
		if (!is_array($array)) {
            return $array;
        }
		$flippedArray = array_flip($array);
		$firstKey = current($flippedArray);
		if (is_numeric($firstKey)) {
			return $array;
		}
		$fixed = [];
		foreach($array as $key => $value) {
			if (checkboxIsChecked($value)) {
				$fixed[] = $key;
			}
		}
		return $fixed;
	}

	/**
	 * Default post types to cache if no post types are specified for cache.
	 *
	 * @param string $format
	 *
	 * @return array|string
	 */
	public function getDefaultPostTypesToCache($format = 'array')
    {
	    $defaults = [
	        'post',
            'page',
            'product'
        ];
	    if ($format === 'array') {
		    return $defaults;
	    } elseif ($format === 'csv') {
		    return formatArrayToCsv($defaults);
	    }
    }

	/**
	 * Ids of posts to exclude from cache.
	 *
	 * @param bool|int $blogId
	 *
	 * @return array|mixed|void|null
	 */
	public function getIdsToExcludeFromCache($blogId = false)
    {
		if (is_numeric($blogId)) {
			$idsToExclude = getBlogOption($blogId, 'fpc_exclude');
			if (!is_array($idsToExclude)) {
                $idsToExclude = [];
            }
			return $idsToExclude;
		}
		if (is_null($this->idsToExcludeCache)) {
			$idsToExclude = getOption( 'fpc_exclude');
			if (!is_array($idsToExclude)) {
                $idsToExclude = [];
            }
			$this->idsToExcludeCache = $idsToExclude;
		}
		return $this->idsToExcludeCache;
	}

	/**
	 * Exclude post from FPC.
	 *
	 * @param int $postId
	 * @param bool $blogId
	 *
	 * @return bool
	 */
	public function excludePostFromCache($postId, $blogId = false)
    {
		return $this->excludePostsFromCache([$postId], $blogId);
	}

	/**
	 * Exclude posts from FPC.
	 *
	 * @param $posts
	 * @param bool|int $blogId
	 *
	 * @return bool
	 */
	public function excludePostsFromCache($posts, $blogId = false)
    {
		$alreadyExcluded = fullPageCache()->getIdsToExcludeFromCache($blogId) ?: [];
		foreach($posts as $postId) {
			if ( ! in_array($postId, $alreadyExcluded) ) {
                $alreadyExcluded[] = $postId;
			}
		}
		return fullPageCache()->setIdsToExcludeFromCache($alreadyExcluded, $blogId);
	}

	/**
	 * Set posts to exclude from cache.
	 *
	 * @param $idsToExclude
	 * @param bool|int $blogId
	 *
	 * @return bool
	 */
	public function setIdsToExcludeFromCache($idsToExclude, $blogId = false)
    {
		$this->idsToExcludeCache = $idsToExclude;
		if ( is_numeric($blogId) ) {
			return updateBlogOption($blogId, 'fpc_exclude', $idsToExclude);
		} else {
			return updateOption('fpc_exclude', $idsToExclude);
		}
	}

	/**
	 * The option name/key we use to store the active state for the Nginx FPC cache.
	 *
	 * @return string
	 */
	private function fpcActiveOptionKey(): string
    {
		return 'fpc_switch';
	}

	/**
	 * Set full page caching is active/inactive, either for current blog or specified blog.
	 *
	 * @param bool $state
	 * @param bool|int $blogId
	 *
	 * @return bool|mixed
	 */
	public function fpcToggleActive(bool $state, $blogId = false)
    {
		if (is_numeric($blogId)) {
			return updateBlogOption($blogId, $this->fpcActiveOptionKey(), $state);
		} else {
			return updateOption($this->fpcActiveOptionKey(), $state);
		}
	}

	/**
	 * Check if we are in a WooCommerce-context and check if we should not cache.
	 *
	 * @return bool
	 */
	private function isWoocommerceNoCachePage(): bool
    {
		return apply_filters('sb_optimizer_fpc_woocommerce_pages_no_cache_bool', ($this->woocommerceIsActive() && (is_cart() || is_checkout() || is_account_page())));
	}

	/**
	 * Check if we are in a WooCommerce-context and check if we should cache.
	 *
	 * @return bool
	 */
	private function isWoocommerceCachePage(): bool
    {
		return apply_filters('sb_optimizer_fpc_woocommerce_pages_cache_bool', ($this->woocommerceIsActive() && (is_shop() || is_product_category() || is_product_tag() || is_product())));
	}
}
