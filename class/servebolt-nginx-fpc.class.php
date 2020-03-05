<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Class Servebolt_Nginx_FPC
 * @package Servebolt
 */
class Servebolt_Nginx_FPC {

	/**
	 * Singleton instance.
	 *
	 * @var null
	 */
	private static $instance = null;

	/**
	 * The post types that are cacheable.
	 *
	 * @var array
	 */
	private $post_types = [];

	/**
	 * @var bool
	 */
	private $alreadySet = false;

	/**
	 * Cache the result of the function "get$CacheablePostTypes" to save some DB-queries.
	 *
	 * @var null
	 */
	private $cacheablePostTypesCache = null;

	/**
	 * Cache the post ids to exclude from cache.
	 *
	 * @var null
	 */
	private $idsToExcludeCache = null;

	/**
	 * Instantiate class.
	 *
	 * @return Servebolt_Nginx_FPC|null
	 */
	public static function getInstance() {
		if ( self::$instance == null ) {
			self::$instance = new Servebolt_Nginx_FPC;
		}

		return self::$instance;
	}

	/**
	 * Check if full page caching is active.
	 *
	 * @return bool
	 */
	public function FPCIsActive() {
		return filter_var( sb_get_option( 'fpc_switch' ), FILTER_VALIDATE_BOOLEAN ) === true;
	}

	/**
	 * Servebolt_Nginx_FPC constructor.
	 */
	private function __construct() {}

	/**
	 * Setup filters.
	 */
	public function setup() {
		add_filter( 'admin_init', [ $this, 'setHeaders' ] );
		add_filter( 'posts_results', [ $this, 'setHeaders' ] );
		add_filter( 'template_include', [ $this, 'lastCall' ] );
	}

	/**
	 * Last call - Run a last call to the set headers function before the template is loaded.
	 *
	 * @param $template
	 *
	 * @return mixed
	 */
	public function lastCall( $template ) {
		$this->setHeaders( [ get_post() ] );

		return $template;
	}

	/**
	 * Ids of posts to exclude from cache.
	 *
	 * @return mixed|void
	 */
	public function getIdsToExclude() {
		if ( is_null( $this->idsToExcludeCache ) ) {
			$this->idsToExcludeCache = sb_get_option( 'fpc_exclude', [] );
		}

		return $this->idsToExcludeCache;
	}

	/**
	 * No cache cookie for logged in users.
	 */
	private function noCacheForLoggedInUsers() {
		if ( is_user_logged_in() ) {
			setcookie( 'no_cache', 1, $_SERVER['REQUEST_TIME'] + 3600, COOKIEPATH, COOKIE_DOMAIN );
		}
	}

	/**
	 * Set cache headers - Determine and set the type of headers to be used.
	 *
	 * @param $posts
	 *
	 * @return mixed
	 */
	public function setHeaders( $posts ) {
		global $wp_query;
		$postType = get_post_type();

		if ( $this->alreadySet ) return $posts;
		$this->alreadySet = true;

		// Set no-cache for all admin pages
		if ( is_admin() || is_user_logged_in() ) {
			$this->noCacheHeaders();
			$this->noCacheForLoggedInUsers();
			return $posts;
		}

		if ( ! isset( $wp_query ) || ! get_post_type() ) {
			$this->alreadySet = false;
			return $posts;
		}

		// Only trigger this function once
		remove_filter( 'posts_results', [$this, 'set_headers'] );

        if ( $this->isWooCommerce() ) {
            $this->noCacheHeaders();
        } elseif ( $this->shouldExcludePost( get_the_ID() ) ) {
	        $this->noCacheHeaders();
        } elseif ( $this->cacheAllPostTypes() ) {
            // Make sure the post type can be cached
	        $this->post_types[] = get_post_type();
	        $this->cacheHeaders();
        }
		elseif ( ( is_front_page() || is_singular() || is_page() ) && $this->cacheActiveForPostType($postType) ) {
			// Make sure the post type can be cached
			$this->post_types[] = $postType;
			$this->cacheHeaders();
		}
		elseif ( is_archive() && $this->canCacheArchive( $posts ) ) {
			// Make sure the archive has only cachable posts
			$this->cacheHeaders();
		}
        elseif( !empty($this->getCacheablePostTypes() ) ) {
            $this->post_types[] = get_post_type();
            if( in_array( get_post_type() , $this->defaultCacheablePostTypes() ) ) $this->cacheHeaders();
        }
        elseif( empty($this->getCacheablePostTypes() ) && ( is_front_page() || is_singular() || is_page() ) ) {
	        $this->cacheHeaders();
        }
		else {
			// Default to no-cache headers
			$this->noCacheHeaders();
		}
		return $posts;
	}

	/**
	 * Check if we should exclude post from cache.
	 *
	 * @param $postId
	 *
	 * @return bool
	 */
	private function shouldExcludePost($postId)
	{
		return in_array($this->getIdsToExclude(), $postId);
	}

	/**
	 * Check if we are in a WooCommerce-context.
	 *
	 * @return bool
	 */
	private function isWooCommerce()
	{
		return class_exists( 'WooCommerce' ) && ( is_cart() || is_checkout() );
	}

	/**
	 * Check if cache is active for given post type.
	 *
	 * @param $postType
	 *
	 * @return bool
	 */
	private function cacheActiveForPostType($postType)
	{
		$cacheablePostTypes = $this->getCacheablePostTypes();
		if ( array_key_exists( $postType, $cacheablePostTypes) && $cacheablePostTypes[$postType] === 'on' ) {
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
	 * Can cache archive.
	 *
	 * @param  array $posts Posts in the archive
	 * @return boolean      Return true if all posts are cacheable
	 */
	private function canCacheArchive( $posts ) {
		foreach ( $posts as $post ) {
			if ( !array_key_exists( $post->post_type, $this->getCacheablePostTypes() ) )
				return false;
			elseif ( !in_array( $post->post_type, $this->post_types ) )
				$this->post_types[] = $post->post_type;
		}
		return true;
	}

	/**
	 * Cache headers - Print headers that encourage caching.
	 */
	static function cacheHeaders() {

	    // Set the default Full Page Cache time
        $fpc_cache_time = 600;

        // Check if the constant SERVEBOLT_FPC_CACHE_TIME is set, and override $servebolt_nginx_cache_time if it is
        if ( defined('SERVEBOLT_FPC_CACHE_TIME') ) {
	        $fpc_cache_time = SERVEBOLT_FPC_CACHE_TIME;
        }

        // Set the default browser cache time
        $browser_cache_time = 600;

        // Check if the constant SERVEBOLT_BROWSER_CACHE_TIME is set, and override $servebolt_browser_cache_time if it is
        if ( defined('SERVEBOLT_BROWSER_CACHE_TIME') ) {
	        $browser_cache_time = SERVEBOLT_BROWSER_CACHE_TIME;
        }

		header_remove('Cache-Control');
		header_remove('Pragma');
		header_remove('Expires');

		// Allow browser to cache content for 10 minutes, or the set browser cache time contant
		header(sprintf('Cache-Control:max-age=%s, public', $browser_cache_time));
		header('Pragma: public');

		// Expire in front-end caches and proxies after 10 minutes, or use the constant if defined.
		$expiryString = gmdate('D, d M Y H:i:s', $_SERVER['REQUEST_TIME'] + $fpc_cache_time) . ' GMT';
		header(sprintf('Expires: %s', $expiryString));
		header('X-Servebolt-Plugin: active');
	}

	/**
	 * No cache headers - Print headers that prevent caching.
	 */
	static function noCacheHeaders() {
		header( 'Cache-Control: max-age=0,no-cache' );
		header( 'Pragma: no-cache' );
		header( 'X-Servebolt-Plugin: active' );
	}

	/**
	 * Get cacheable post types.
	 *
	 * @return array A list of cacheable post types.
	 */
	private function getCacheablePostTypes() {
		if ( is_null($this->cacheablePostTypesCache) ) {
			$post_types = sb_get_option('fpc_settings');
			$this->cacheablePostTypesCache = is_array($post_types) ? $post_types : $this->default_cacheable_post_types();
		}
		return $this->cacheablePostTypesCache;
	}

	/**
	 * Default cacheable post types.
	 *
	 * @param string $format
	 *
	 * @return array|string
	 */
	public function defaultCacheablePostTypes($format = 'array') {
	    $defaults = [
	        'post',
            'page',
            'product'
        ];
	    if ( $format === 'array' ) {
		    return $defaults;
	    } elseif ( $format === 'csv' ) {
		    return implode(',', $defaults);
	    }
    }
}
