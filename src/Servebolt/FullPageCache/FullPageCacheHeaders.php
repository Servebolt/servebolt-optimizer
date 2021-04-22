<?php

namespace Servebolt\Optimizer\FullPageCache;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Traits\Singleton;
use function Servebolt\Optimizer\Helpers\checkboxIsChecked;
use function Servebolt\Optimizer\Helpers\isAjax;
use function Servebolt\Optimizer\Helpers\formatArrayToCsv;
use function Servebolt\Optimizer\Helpers\isCron;
use function Servebolt\Optimizer\Helpers\isWpRest;
use function Servebolt\Optimizer\Helpers\smartGetOption;
use function Servebolt\Optimizer\Helpers\smartUpdateOption;
use function Servebolt\Optimizer\Helpers\woocommerceIsActive;
use function Servebolt\Optimizer\Helpers\writeLog;

/**
 * Class FullPageCacheHeaders
 * @package Servebolt
 *
 * This class handles the actual cache handling by passing headers from WordPress/Apache to the Nginx web server.
 * Note: Only relevant for websites hosted at Servebolt!
 */
class FullPageCacheHeaders
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
     * Alias for "getInstance".
     */
    public static function init(): void
    {
        self::getInstance();
    }

	/**
	 * FullPageCacheHeaders constructor.
	 */
	private function __construct()
    {

		// Handle "no_cache"-header for authenticated users.
        FullPageCacheAuthHandling::init();

		// Unauthenticated cache handling
        if ($this->isFrontEnd()) {
            add_filter('posts_results', [$this, 'setHeaders']);
            add_filter('template_include', [$this, 'lastCall']);
        }
	}

    /**
     * Check whether we are front-end or not.
     *
     * @return bool
     */
	private function isFrontEnd(): bool
    {
        if (is_admin() || isAjax() || isWpRest() || isCron()) {
            return false;
        }
        return true;
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
        if (!FullPageCacheSettings::fpcIsActive() || is_admin() || isAjax() || is_user_logged_in()) {
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
		} elseif (CachePostExclusion::shouldExcludePostFromCache(get_the_ID())) {
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
		} elseif (empty(self::getPostTypesToCache())) {
			$this->postTypes[] = $postType;
			if ($debug) {
                $this->header('Cache-trigger: 7');
            }
			if (in_array($postType , self::getDefaultPostTypesToCache())) {
				$this->cacheHeaders();
				if ($debug) {
                    $this->header('Cache-trigger: 8');
                }
			}
		} elseif (empty(self::getPostTypesToCache()) && (is_front_page() || is_singular() || is_page())) {
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
     * Check if we are in a WooCommerce-context and check if we should not cache.
     *
     * @return bool
     */
    private function isWoocommerceNoCachePage(): bool
    {
        return apply_filters('sb_optimizer_fpc_woocommerce_pages_no_cache_bool', (woocommerceIsActive() && (is_cart() || is_checkout() || is_account_page())));
    }

    /**
     * Check if we are in a WooCommerce-context and check if we should cache.
     *
     * @return bool
     */
    private function isWoocommerceCachePage(): bool
    {
        return apply_filters('sb_optimizer_fpc_woocommerce_pages_cache_bool', (woocommerceIsActive() && (is_shop() || is_product_category() || is_product_tag() || is_product())));
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
	 * @param string $key
	 * @param null|string $value
	 */
	public function header(string $key, ?string $value = null)
    {

        if (!$value) {
            $string = $key;
        } else {
            $string = $key . ': ' . $value;
        }

		// Abort if headers are already sent
		if (headers_sent() && !$this->allowForceHeaders) {
            writeLog(sprintf('Servebolt Optimizer attempted to set header "%s", but headers were already sent.', $string));
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
	 * Check if cache is active for given post type.
	 *
	 * @param $postType
	 *
	 * @return bool
	 */
	private function cacheActiveForPostType($postType): bool
    {
		if (in_array($postType, (array) self::getPostTypesToCache())) {
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
            if (!in_array($post->post_type, self::getPostTypesToCache())) {
				return false;
			} elseif (!in_array($post->post_type, (array) $this->postTypes)) {
				$this->postTypes[] = $post->post_type;
			}
		}
		return true;
	}

	/**
	 * Cache headers - Print headers that encourage caching.
	 */
	private function cacheHeaders()
    {
        do_action('sb_optimizer_fpc_cache_headers', $this);

        if (apply_filters('sb_optimizer_fpc_send_sb_cache_headers', true)) {
            // Check if the constant SERVEBOLT_FPC_CACHE_TIME is set, and override $serveboltNginxCacheTime if it is
            if (defined('SERVEBOLT_FPC_CACHE_TIME')) {
                $this->fpcCacheTime = SERVEBOLT_FPC_CACHE_TIME;
            }

            // Check if the constant SERVEBOLT_BROWSER_CACHE_TIME is set, and override $serveboltBrowserCacheTime if it is
            if (defined('SERVEBOLT_BROWSER_CACHE_TIME')) {
                $this->browserCacheTime = SERVEBOLT_BROWSER_CACHE_TIME;
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
	}

	/**
	 * No cache headers - Print headers that prevent caching.
	 */
	private function noCacheHeaders(): void
    {
        do_action('sb_optimizer_fpc_no_cache_headers', $this);
        if (apply_filters('sb_optimizer_fpc_send_sb_cache_headers', true)) {
            $this->header( 'Cache-Control: max-age=0,no-cache,s-maxage=0' );
            $this->header( 'Pragma: no-cache' );
            $this->header( 'X-Servebolt-Plugin: active' );
        }
	}

	/**
	 * The option name/key we use to store the cacheable post types for the Nginx FPC cache.
	 *
	 * @return string
	 */
	private static function fpcCacheablePostTypesOptionKey(): string
    {
		return 'fpc_settings';
	}

	/**
	 * Set cacheable post types.
	 *
	 * @param $postTypes
	 * @param null|int $blogId
	 *
	 * @return bool|mixed
	 */
	public static function setCacheablePostTypes($postTypes, ?int $blogId = null)
    {
        return smartUpdateOption($blogId, self::fpcCacheablePostTypesOptionKey(), $postTypes);
	}

	/**
	 * Get post types that we can apply cache for.
	 *
	 * @param bool $includeAll
	 *
	 * @return array
	 */
	public static function getAvailablePostTypesToCache($includeAll = false): array
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
	 * @param null|int $blogId The ID of the blog we would like to interact with.
	 *
	 * @return array|mixed|string|void|null A list of cacheable post types.
	 */
	public static function getPostTypesToCache(bool $respectDefaultFallback = true, bool $respectAll = true, ?int $blogId = null)
    {

        $postTypesToCache = smartGetOption($blogId, self::fpcCacheablePostTypesOptionKey());

		// Make sure we migrate from old array structure
        $postTypesToCache = self::maybeFixPostTypeArrayStructure($postTypesToCache);

		if ($respectAll && in_array('all', (array) $postTypesToCache)) {
			return array_keys(self::getAvailablePostTypesToCache());
		}

		// Check if we should return default post types instead of not post types are set
		if ($respectDefaultFallback && (!is_array($postTypesToCache) || empty($postTypesToCache))) {
            $postTypesToCache = self::getDefaultPostTypesToCache();
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
	private static function maybeFixPostTypeArrayStructure($array)
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
	public static function getDefaultPostTypesToCache(string $format = 'array')
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
}
