<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Class Servebolt_Nginx_FPC
 * @package Servebolt
 *
 * This class handles the Cache handling by passing headers from WordPress/Apache to the Nginx web server. Only relevant for websites hosted at Servebolt.
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
	 * Whether headers are already set for this request.
	 *
	 * @var bool
	 */
	private $already_set = false;

	/**
	 * Cache the post ids to exclude from cache.
	 *
	 * @var null
	 */
	private $ids_to_exclude_cache = null;

	/**
	 * Instantiate class.
	 *
	 * @return Servebolt_Nginx_FPC|null
	 */
	public static function get_instance() {
		if ( self::$instance == null ) {
			self::$instance = new Servebolt_Nginx_FPC;
		}
		return self::$instance;
	}

	/**
	 * Servebolt_Nginx_FPC constructor.
	 */
	private function __construct() {}

	/**
	 * Setup filters.
	 */
	public function setup() {
		add_filter( 'admin_init', [ $this, 'set_headers' ] );
		add_filter( 'posts_results', [ $this, 'set_headers' ] );
		add_filter( 'template_include', [ $this, 'last_call' ] );
	}

	/**
	 * Last call - Run a last call to the set headers function before the template is loaded.
	 *
	 * @param $template
	 *
	 * @return mixed
	 */
	public function last_call( $template ) {
		$this->set_headers( [ get_post() ] );
		return $template;
	}

	/**
	 * Set cache headers - Determine and set the type of headers to be used.
	 *
	 * @param $posts
	 *
	 * @return mixed
	 */
	public function set_headers( $posts ) {
		global $wp_query;
		$post_type = get_post_type();

		if ( $this->already_set ) return $posts;
		$this->already_set = true;

		// Set no-cache for all admin pages
		if ( is_admin() || is_user_logged_in() ) {
			$this->no_cache_headers();
			$this->no_cache_for_logged_in_users();
			return $posts;
		}

		if ( ! isset( $wp_query ) || ! get_post_type() ) {
			$this->already_set = false;
			return $posts;
		}

		// Only trigger this function once
		remove_filter( 'posts_results', [$this, 'set_headers'] );

        if ( $this->is_woocommerce() ) {
            $this->no_cache_headers();
        } elseif ( $this->should_exclude_post_from_cache( get_the_ID() ) ) {
	        $this->no_cache_headers();
        } elseif ( $this->cache_all_post_types() ) {
            // Make sure the post type can be cached
	        $this->post_types[] = get_post_type();
	        $this->cache_headers();
        } elseif ( ( is_front_page() || is_singular() || is_page() ) && $this->cache_active_for_post_type($post_type) ) {
			// Make sure the post type can be cached
			$this->post_types[] = $post_type;
			$this->cache_headers();
		} elseif ( is_archive() && $this->should_cache_archive( $posts ) ) {
			// Make sure the archive has only cachable posts
			$this->cache_headers();
		} elseif( !empty($this->get_post_types_to_cache() ) ) {
            $this->post_types[] = get_post_type();
            if( in_array( get_post_type() , $this->get_default_post_types_to_cache() ) ) $this->cache_headers();
        } elseif( empty($this->get_post_types_to_cache() ) && ( is_front_page() || is_singular() || is_page() ) ) {
	        $this->cache_headers();
        } else {
			// Default to no-cache headers
			$this->no_cache_headers();
		}
		return $posts;
	}

	/**
	 * No cache cookie for logged in users.
	 */
	private function no_cache_for_logged_in_users() {
		if ( is_user_logged_in() ) {
			setcookie( 'no_cache', 1, $_SERVER['REQUEST_TIME'] + 3600, COOKIEPATH, COOKIE_DOMAIN );
		}
	}

	/**
	 * Check if we should exclude post from cache.
	 *
	 * @param $post_id
	 *
	 * @return bool
	 */
	private function should_exclude_post_from_cache($post_id) {
		$ids_to_exclude = $this->get_ids_to_exclude_from_cache();
		return is_array($ids_to_exclude) && in_array($post_id, $ids_to_exclude);
	}

	/**
	 * Check if we are in a WooCommerce-context.
	 *
	 * @return bool
	 */
	private function is_woocommerce() {
		return class_exists( 'WooCommerce' ) && ( is_cart() || is_checkout() );
	}

	/**
	 * Check if cache is active for given post type.
	 *
	 * @param $post_type
	 *
	 * @return bool
	 */
	private function cache_active_for_post_type($post_type) {
		if ( in_array( $post_type, (array) $this->get_post_types_to_cache()) ) {
			return true;
		}
		return false;
	}

	/**
	 * Check if cache is active for all post types.
	 *
	 * @return bool
	 */
	private function cache_all_post_types() {
		return $this->cache_active_for_post_type('all');
	}

	/**
	 * Check if we should cache an archive.
	 *
	 * @param  array $posts Posts in the archive
	 * @return boolean      Return true if all posts are cacheable
	 */
	private function should_cache_archive( $posts ) {
		foreach ( $posts as $post ) {
			if ( ! array_key_exists( $post->post_type, $this->get_post_typesto_cache() ) ) {
				return false;
			} elseif ( !in_array( $post->post_type, $this->post_types ) ) {
				$this->post_types[] = $post->post_type;
			}
		}
		return true;
	}

	/**
	 * Cache headers - Print headers that encourage caching.
	 */
	static function cache_headers() {

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
	private function no_cache_headers() {
		header( 'Cache-Control: max-age=0,no-cache' );
		header( 'Pragma: no-cache' );
		header( 'X-Servebolt-Plugin: active' );
	}

	/**
	 * The option name/key we use to store the cacheable post types for the Nginx FPC cache.
	 *
	 * @return string
	 */
	private function fpc_cacheable_post_types_option_key() {
		return 'fpc_settings';
	}

	/**
	 * Set cacheable post types.
	 *
	 * @param $post_types
	 * @param bool $blog_id
	 *
	 * @return bool|mixed
	 */
	public function set_cacheable_post_types($post_types, $blog_id = false) {
		if ( is_numeric($blog_id) ) {
			return sb_update_blog_option($blog_id, $this->fpc_cacheable_post_types_option_key(), $post_types);
		} else {
			return sb_update_option($this->fpc_cacheable_post_types_option_key(), $post_types);
		}
	}

	/**
	 * Get post types that we can apply cache for.
	 *
	 * @param bool $include_all
	 *
	 * @return mixed
	 */
	public function get_available_post_types_to_cache($include_all = false) {

		$post_types = get_post_types(['public' => true], 'objects');

		$array = [];
		if ( $include_all ) {
			$array['all'] = sb__('All');
		}

		foreach ($post_types as $post_type) {
			$array[$post_type->name] = $post_type->labels->singular_name;
		}

		return $array;
	}

	/**
	 * Get the post types to cache.
	 *
	 * @param bool $respect_default_fallback Whether to fall back on default post types.

	 * @param bool $respect_all Whether to return all post types if all is selected.
	 * @param bool $blog_id The ID of the blog we would like to interact with.
	 *
	 * @return array|mixed|string|void|null A list of cacheable post types.
	 */
	public function get_post_types_to_cache($respect_default_fallback = true, $respect_all = true, $blog_id = false) {

		if ( is_numeric($blog_id) ) {
			$post_types_to_cache = sb_get_blog_option($blog_id, $this->fpc_cacheable_post_types_option_key());
		} else {
			$post_types_to_cache = sb_get_option($this->fpc_cacheable_post_types_option_key());
		}

		// Make sure we migrate from old array structure
		$post_types_to_cache = $this->maybe_fix_post_type_array_structure($post_types_to_cache);

		if ( $respect_all && in_array('all', $post_types_to_cache) ) {
			return array_keys(sb_nginx_fpc()->get_available_post_types_to_cache());
		}

		// Check if we should return default post types instead of not post types are set
		if ( $respect_default_fallback && ( ! is_array($post_types_to_cache) || empty($post_types_to_cache) ) ) {
			$post_types_to_cache = $this->get_default_post_types_to_cache();
		}

		return $post_types_to_cache;
	}

	/**
	 * Convert array structure from old to new (to keep backwards compatibility with 1.6.2<=).
	 *
	 * @param $array
	 *
	 * @return array
	 */
	private function maybe_fix_post_type_array_structure($array) {
		if ( ! is_array($array) ) return $array;
		$flipped_array = array_flip($array);
		$first_key = current($flipped_array);
		if ( is_numeric($first_key) ) {
			return $array;
		}
		$fixed = [];
		foreach($array as $key => $value) {
			if ( sb_checkbox_true($value) ) {
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
	public function get_default_post_types_to_cache($format = 'array') {
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

	/**
	 * Ids of posts to exclude from cache.
	 *
	 * @return mixed|void
	 */
	public function get_ids_to_exclude_from_cache() {
		if ( is_null( $this->ids_to_exclude_cache ) ) {
			$ids_to_exclude = sb_get_option( 'fpc_exclude');
			if ( ! is_array($ids_to_exclude) ) $ids_to_exclude = [];
			$this->ids_to_exclude_cache = $ids_to_exclude;
		}
		return $this->ids_to_exclude_cache;
	}

	/**
	 * Set posts to exclude from cache.
	 *
	 * @param $ids_to_exclude
	 *
	 * @return bool
	 */
	public function set_ids_to_exclude_from_cache($ids_to_exclude) {
		$this->ids_to_exclude_cache = $ids_to_exclude;
		return sb_update_option( 'fpc_exclude', $ids_to_exclude );
	}

	/**
	 * The option name/key we use to store the active state for the Nginx FPC cache.
	 *
	 * @return string
	 */
	private function fpc_active_option_key() {
		return 'fpc_switch';
	}

	/**
	 * Check if full page caching is active with optional blog check.
	 *
	 * @param bool $blog_id
	 *
	 * @return bool
	 */
	public function fpc_is_active($blog_id = false) {
		if ( is_numeric($blog_id) ) {
			return sb_checkbox_true(sb_get_blog_option($blog_id, $this->fpc_active_option_key()));
		} else {
			return sb_checkbox_true(sb_get_option($this->fpc_active_option_key()));
		}
	}

	/**
	 * Set full page caching is active/inactive, either for current blog or specified blog.
	 *
	 * @param bool $state
	 * @param bool $blog_id
	 *
	 * @return bool|mixed
	 */
	public function fpc_toggle_active( bool $state, $blog_id = false) {
		if ( is_numeric($blog_id) ) {
			return sb_update_blog_option($blog_id, $this->fpc_active_option_key(), $state);
		} else {
			return sb_update_option($this->fpc_active_option_key(), $state);
		}
	}
}
