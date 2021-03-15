<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Include crypto-class
require __DIR__ . '/classes/sb-crypto.class.php';

if ( ! function_exists('sb_cloudflare_proxy_in_use') ) {
	/**
	 * Check if Cloudflare Proxy is used by analyzing the request headers, and if not present then send a request to the front page and analyze the headers again.
	 *
	 * @return bool
	 */
	function sb_cloudflare_proxy_in_use() {

		$cf_headers_to_look_for = ['cf-request-id', 'cf-ray'];

		$headers = array_keys( array_change_key_case( (array) getallheaders(), CASE_LOWER ) );
		foreach( $headers as $key ) {
			if ( in_array($key, $cf_headers_to_look_for) ) {
				return true;
			}
		}

		if ( ! array_key_exists('sb-cf-check', $_GET) ) {
			$headers = array_keys( array_change_key_case( get_headers( get_site_url('?sb-cf-check'), true ), CASE_LOWER ) );
			foreach( $headers as $key ) {
				if ( in_array($key, $cf_headers_to_look_for) ) {
					return true;
				}
			}
		}

		return false;

	}
}

if ( ! function_exists('sb_paginate_links_as_array') ) {
    /**
     * Create an array of paginated links based on URL and number of pages.
     *
     * @param $url
     * @param $pages_needed
     * @param array $args
     * @return array|string|void
     */
    function sb_paginate_links_as_array($url, $pages_needed, $args = []) {
        if ( ! is_numeric($pages_needed) || $pages_needed <= 1 ) return [$url];

        $base_args = apply_filters('sb_paginate_links_as_array_args', [
            'base'      => $url . '%_%',
            'type'      => 'array',
            'current'   => false,
            'total'     => $pages_needed,
            'show_all'  => true,
            'prev_next' => false,
        ]);

        $args = wp_parse_args( $args, $base_args );
        $links = paginate_links($args);

        $links = array_map(function($link) {
            preg_match_all('/<a[^>]+href=([\'"])(?<href>.+?)\1[^>]*>/i', $link, $result);
            if ( array_key_exists('href', $result) && count($result['href']) === 1 && ! empty($result['href']) ) {
                $url = current($result['href']);
                $url = strtok($url, '?'); // Remove query string
                return $url;
            }
            return false;
        }, $links);

        $links = array_filter($links, function($link) {
            return $link !== false;
        });

        return $links;
    }
}

if ( ! function_exists('sb_smart_update_option') ) {
	/**
   * A function that will store the option at the right place (in current blog or a specified blog).
   *
	 * @param $blog_id
	 * @param $option_name
	 * @param $value
	 * @param bool $assert_update
	 *
	 * @return bool|mixed
	 */
	function sb_smart_update_option($blog_id, $option_name, $value, $assert_update = true) {
		if ( is_numeric($blog_id) ) {
			$result = sb_update_blog_option($blog_id, $option_name, $value, $assert_update);
		} else {
			$result = sb_update_option($option_name, $value, $assert_update);
		}
		return $result;
	}
}

if ( ! function_exists('sb_smart_get_option') ) {
	/**
	 * A function that will get the option at the right place (in current blog or a specified blog).
	 *
	 * @param $blog_id
	 * @param $option_name
	 * @param bool $default
	 *
	 * @return mixed|void
	 */
	function sb_smart_get_option($blog_id, $option_name, $default = false) {
		if ( is_numeric($blog_id) ) {
			$result = sb_get_blog_option($blog_id, $option_name, $default);
		} else {
			$result = sb_get_option($option_name, $default);
		}
		return $result;
	}
}

if ( ! function_exists('sb_feature_active') ) {
	/**
   * Check whether a feature is active.
   *
	 * @param $feature
	 *
	 * @return bool|null
	 */
  function sb_feature_active($feature) {
    switch ($feature) {
        case 'cf_image_resize':
          // Only active when defined is set, or if already active - this is to keep it beta for now (slightly hidden).
          return ( defined('SERVEBOLT_CF_IMAGE_RESIZE_ACTIVE') && SERVEBOLT_CF_IMAGE_RESIZE_ACTIVE === true ) || ( sb_cf_image_resize_control() )->resizing_is_active();
          break;
        case 'sb_asset_auto_version':
            return sb_general_settings()->asset_auto_version();
            break;
	    case 'cf_cache':
		    return true;
		    break;
    }
    return null;

  }
}

if ( ! function_exists('sb_performance_checks') ) {
	/**
	 * Get Servebolt_Performance_Checks-instance.
	 *
	 * @return Servebolt_Performance_Checks|null
	 */
	function sb_performance_checks() {
		require_once SERVEBOLT_PATH . 'admin/performance-checks.php';
		return Servebolt_Performance_Checks::get_instance();
	}
}

if ( ! function_exists('sb_cf_image_resize_control') ) {
	/**
	 * Get SB_Image_Resize_Control-instance.
	 *
	 * @return SB_Image_Resize_Control|null
	 */
	function sb_cf_image_resize_control() {
	  require_once SERVEBOLT_PATH . 'classes/cloudflare-image-resize/sb-image-resize-control.php';
		return SB_Image_Resize_Control::get_instance();
	}
}

if ( ! function_exists('sb_cf_image_resizing') ) {
	/**
	 * Get CF_Image_Resizing-instance.
	 *
	 * @return CF_Image_Resizing|null
	 */
	function sb_cf_image_resizing() {
		require_once SERVEBOLT_PATH . 'admin/cf-image-resizing.php';
		return CF_Image_Resizing::get_instance();
	}
}

if ( ! function_exists('sb_optimize_db') ) {
  /**
   * Get Servebolt_Optimize_DB-instance.
   *
   * @return Servebolt_Performance_Checks|null
   */
  function sb_optimize_db() {
    require_once SERVEBOLT_PATH . 'admin/optimize-db/optimize-db.php';
    return Servebolt_Optimize_DB::get_instance();
  }
}

if ( ! function_exists('sb_cf_cache') ) {
  /**
   * Get Servebolt_Checks-instance.
   *
   * @return Servebolt_CF_Cache|null
   */
  function sb_cf_cache() {
    require_once SERVEBOLT_PATH . 'classes/cloudflare-cache/sb-cf-cache.php';
    return Servebolt_CF_Cache::get_instance();
  }
}

if ( ! function_exists('sb_cf_cache_controls') ) {
  /**
   * Get Servebolt_Checks-instance.
   *
   * @return Servebolt_Checks|null
   */
  function sb_cf_cache_admin_controls() {
    require_once SERVEBOLT_PATH . 'admin/cf-cache-admin-controls.php';
    return CF_Cache_Admin_Controls::get_instance();
  }
}

if ( ! function_exists('sb_nginx_fpc_controls') ) {
  /**
   * Get Nginx_FPC_Controls-instance.
   *
   * @return Servebolt_Nginx_FPC|null
   */
  function sb_nginx_fpc_controls() {
    require_once SERVEBOLT_PATH . 'admin/nginx-fpc-controls.php';
    return Nginx_FPC_Controls::get_instance();
  }
}

if ( ! function_exists('sb_general_settings') ) {
    /**
     * Get SB_General_Settings-instance.
     *
     * @return SB_General_Settings|null
     */
    function sb_general_settings() {
        require_once SERVEBOLT_PATH . 'admin/general-settings.php';
        return SB_General_Settings::get_instance();
    }
}



if ( ! function_exists('sb_nginx_fpc') ) {
  /**
   * Get Servebolt_Nginx_FPC-instance.
   *
   * @return Servebolt_Nginx_FPC|null
   */
  function sb_nginx_fpc() {
    require_once SERVEBOLT_PATH . 'classes/nginx-fpc/sb-nginx-fpc.php';
    return Servebolt_Nginx_FPC::get_instance();
  }
}

if ( ! function_exists('sb_checks') ) {
  /**
   * Get Servebolt_Checks-instance.
   *
   * @return Servebolt_Optimize_DB|null
   */
  function sb_checks() {
    require_once SERVEBOLT_PATH . 'admin/optimize-db/checks.php';
    return Servebolt_Checks::get_instance();
  }
}

if ( ! function_exists('sb_boolean_to_state_string') ) {
  /**
   * Convert a boolean to a human readable string.
   *
   * @param $state
   *
   * @return bool|null
   */
  function sb_boolean_to_state_string($state) {
    return $state === true ? 'active' : 'inactive';
  }
}

if ( ! function_exists('sb_boolean_to_string') ) {
    /**
     * Convert a type boolean to a verbose boolean string.
     *
     * @param $state
     *
     * @return bool|null
     */
    function sb_boolean_to_string($state) {
        return $state === true ? 'true' : 'false';
    }
}

if ( ! function_exists('sb_is_cli') ) {
  /**
   * Check if we are running as CLI.
   *
   * @return bool
   */
  function sb_is_cli() {
    return ( defined( 'WP_CLI' ) && WP_CLI );
  }
}

if ( ! function_exists('sb_is_cron') ) {
  /**
   * Check if execution is initiated by cron.
   *
   * @return bool
   */
  function sb_is_cron() {
    return ( defined( 'DOING_CRON' ) && DOING_CRON );
  }
}

if ( ! function_exists('sb_cf_cache_purge_object') ) {
    /**
     * Create a new instance of SB_CF_Cache_Purge_Object.
     *
     * @param bool $id
     * @param string $type
     * @param array $args
     * @return SB_CF_Cache_Purge_Object
     */
    function sb_cf_cache_purge_object($id = false, $type = 'post', $args = []) {
        require_once __DIR__ . '/classes/cloudflare-cache/sb-cf-cache-purge-object.php';
        return new SB_CF_Cache_Purge_Object($id, $type, $args);
    }
}

if ( ! function_exists('sb_generic_optimizations') ) {
  /**
   * Add minor improvements to WP.
   */
  function sb_generic_optimizations() {

    if ( apply_filters('sb_optimizer_skip_generic_optimizations', false) ) return;

    // Disable CONCATENATE_SCRIPTS to get rid of some DDOS-attacks
    if ( ! defined('CONCATENATE_SCRIPTS') ) {
      define('CONCATENATE_SCRIPTS', false);
    }

    // Hide the meta tag generator from head and RSS
    add_filter('the_generator', '__return_empty_string');
    remove_action('wp_head', 'wp_generator');
  }
}

if ( ! function_exists('sb_format_post_type') ) {
  /**
   * Format a post type slug.
   *
   * @param $post_type
   *
   * @return mixed|string
   */
  function sb_format_post_type($post_type) {
    $post_type = str_replace('_', ' ', $post_type);
    $post_type = str_replace('-', ' ', $post_type);
    $post_type = ucfirst($post_type);
    return $post_type;
  }
}

if ( ! function_exists('sb_post_exists') ) {
  /**
   * Check if post exists.
   *
   * @param $post_id
   *
   * @return bool
   */
  function sb_post_exists($post_id) {
    return get_post_status($post_id) !== false;
  }
}

if ( ! function_exists('sb_resolve_post_ids') ) {
  /**
   * Convert an array of post IDs into array of title and Post ID.
   *
   * @param $posts
   * @param bool $blog_id
   *
   * @return array
   */
  function sb_resolve_post_ids($posts, $blog_id = false) {
    return array_map(function($post_id) use ($blog_id) {
      $title = sb_get_the_title($post_id, $blog_id);
      return $title ? $title . ' (' . $post_id . ')' : $post_id;
    }, $posts);
  }
}

if ( ! function_exists('sb_get_the_title') ) {
  /**
   * Get the title with optional blog-parameter.
   *
   * @param $post_id
   * @param bool $blog_id
   *
   * @return string
   */
  function sb_get_the_title($post_id, $blog_id = false) {
    if ( $blog_id ) switch_to_blog($blog_id);
    $title = get_the_title($post_id);
    if ( $blog_id ) restore_current_blog();
    return $title;
  }
}

if ( ! function_exists('sb_checkbox_true') ) {
  /**
   * Check if a value is either "on" or boolean and true.
   *
   * @param $value
   *
   * @return bool
   */
  function sb_checkbox_true($value) {
    return $value === 'on' || filter_var($value, FILTER_VALIDATE_BOOLEAN) === true;
  }
}

if ( ! function_exists('sb_cf_error') ) {
  /**
   * Log an Cloudflare error.
   *
   * @param $exception
   *
   * @return bool
   */
  function sb_cf_error($exception) {
    sb_write_log($exception->getMessage());
    return false;
  }
}

if ( ! function_exists('sb_get_text_domain') ) {
  /**
   * Get plugin text domain.
   *
   * @return string
   */
  function sb_get_text_domain() {
    return 'servebolt-wp';
  }
}

if ( ! function_exists('sb_esc_html__') ) {
  /**
   * Translate function wrapper.
   *
   * @param $string
   * @param null $domain
   *
   * @return string|void
   */
  function sb_esc_html__($string, $domain = null) {
    return esc_html__($string, $domain ?: sb_get_text_domain());
  }
}

if ( ! function_exists('sb_esc_html_e') ) {
  /**
   * Translate function wrapper.
   *
   * @param $string
   * @param null $domain
   *
   * @return mixed
   */
  function sb_esc_html_e($string, $domain = null) {
    return esc_html_e($string, $domain ?: sb_get_text_domain());
  }
}

if ( ! function_exists('sb__') ) {
  /**
   * Translate function wrapper.
   *
   * @param $string
   * @param null $domain
   *
   * @return string|void
   */
  function sb__($string, $domain = null) {
    return __($string, $domain ?: sb_get_text_domain());
  }
}

if ( ! function_exists('sb_e') ) {
  /**
   * Translate function wrapper.
   *
   * @param $string
   * @param null $domain
   */
  function sb_e($string, $domain = null) {
    _e($string, $domain ?: sb_get_text_domain());
  }
}

if ( ! function_exists('host_is_servebolt') ) {
  /**
   * Check if the site is hosted at Servebolt.
   *
   * @return bool
   */
  function host_is_servebolt() {
    if ( defined('HOST_IS_SERVEBOLT_OVERRIDE') && is_bool(HOST_IS_SERVEBOLT_OVERRIDE) ) {
        return HOST_IS_SERVEBOLT_OVERRIDE;
    }
    foreach (['SERVER_ADMIN', 'SERVER_NAME'] as $key) {
      if (array_key_exists($key, $_SERVER)) {
        if ((boolean) preg_match('/(servebolt|raskesider)\.([\w]{2,63})$/', $_SERVER[$key])) {
          return true;
        }
      }
    }
    return false;
  }
}

if ( ! function_exists('sb_remove_keys_from_array') ) {
  /**
   * Remove elements from array by keys.
   *
   * @param $arguments
   * @param $blacklisted_keys
   *
   * @return mixed
   */
  function sb_remove_keys_from_array($arguments, $blacklisted_keys) {
    foreach ( $blacklisted_keys as $key ) {
      if ( array_key_exists($key, $arguments) ) {
        unset($arguments[$key]);
      }
    }
    return $arguments;
  }
}

if ( ! function_exists('sb_is_ajax') ) {
    /**
     * Check whether this is an AJAX-request.
     *
     * @return bool
     */
    function sb_is_ajax() {
        return defined('DOING_AJAX') && DOING_AJAX;
    }
}

if ( ! function_exists('sb_get_ajax_nonce') ) {
  /**
   * Get ajax nonce.
   */
  function sb_get_ajax_nonce() {
    return wp_create_nonce(sb_get_ajax_nonce_key());
  }
}

if ( ! function_exists('sb_get_ajax_nonce_key') ) {
  /**
   * Get ajax nonce key, generate one if it does not exists.
   *
   * @return mixed|string|void
   */
  function sb_get_ajax_nonce_key() {
    return sb_generate_random_permanent_key('ajax_nonce');
  }
}

if ( ! function_exists('sb_generate_random_permanent_key') ) {
  /**
   * Generate a random key stored in the database.
   *
   * @param $name
   * @param bool $blog_id
   *
   * @return mixed|string|void
   */
  function sb_generate_random_permanent_key($name, $blog_id = false) {
    if ( is_numeric($blog_id) ) {
      $key = sb_get_blog_option($blog_id, $name);
    } else {
      $key = sb_get_option($name);
    }
    if ( ! $key ) {
      $key = sb_generate_random_string(36);
      if ( is_numeric($blog_id) ) {
        sb_update_blog_option($blog_id, $name, $key);
      } else {
        sb_update_option($name, $key);
      }
    }
    return $key;
  }
}

if ( ! function_exists('sb_generate_random_string') ) {
  /**
   * Generate a random string.
   *
   * @param $length
   *
   * @return string
   */
  function sb_generate_random_string($length) {
    $include_chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_-@|';
    $char_length = strlen($include_chars);
    $random_string = '';
    for ($i = 0; $i < $length; $i++) {
      $random_string .= $include_chars [rand(0, $char_length - 1)];
    }
    return $random_string;
  }
}

if ( ! function_exists('sb_is_url') ) {
    /**
     * Whether a string contains a valid URL.
     *
     * @param $url
     * @return bool
     */
    function sb_is_url($url) {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
}

if ( ! function_exists('sb_view') ) {
  /**
   * Display view.
   *
   * @param $path
   * @param array $arguments
   * @param bool $echo
   *
   * @return bool|false|string|void
   */
  function sb_view($path, $arguments = [], $echo = true) {
    $path = SERVEBOLT_PATH . $path . '.php';
    if ( ! file_exists($path) || ! is_readable($path) ) return false;
    if ( is_array($arguments) ) {
      $arguments = sb_remove_keys_from_array($arguments, ['path', 'arguments', 'echo', 'html']);
      extract($arguments);
    }
    ob_start();
    require $path;
    $html = ob_get_contents();
    ob_end_clean();
    if ( $echo ) {
      echo $html;
      return;
    }
    return $html;
  }
}

if ( ! function_exists('sb_get_option_name') ) {
  /**
   * Get option name by key.
   *
   * @param $option
   *
   * @return string
   */
  function sb_get_option_name($option) {
    return 'servebolt_' . $option;
  }
}

if ( ! function_exists('sb_get_option') ) {
  /**
   * Get option.
   *
   * @param $option_name
   * @param bool $default
   *
   * @return mixed|void
   */
  function sb_get_option($option_name, $default = false) {
    $full_option_name = sb_get_option_name($option_name);
    $value = get_option($full_option_name, $default);
    return apply_filters('sb_optimizer_get_option_' . $full_option_name, $value);
  }
}

if ( ! function_exists('sb_update_option') ) {
  /**
   * Update option.
   *
   * @param $option_name
   * @param $value
   * @param bool $assert_update
   *
   * @return bool
   */
  function sb_update_option($option_name, $value, $assert_update = true) {
    $full_option_name = sb_get_option_name($option_name);
    $result = update_option($full_option_name, $value);
    if ( $assert_update && ! $result ) {
      $current_value = sb_get_option($option_name);
      return ( $current_value == $value );
    }
    return true;
  }
}

if ( ! function_exists('sb_delete_option') ) {
  /**
   * Delete option.
   *
   * @param $option
   *
   * @return bool
   */
  function sb_delete_option($option) {
    return delete_option(sb_get_option_name($option));
  }
}

if ( ! function_exists('sb_get_blog_option') ) {
  /**
   * Get blog option.
   *
   * @param $blog_id
   * @param $option_name
   * @param bool $default
   *
   * @return mixed
   */
  function sb_get_blog_option($blog_id, $option_name, $default = false) {
    $full_option_name = sb_get_option_name($option_name);
    $value = get_blog_option($blog_id, $full_option_name, $default);
    return apply_filters('sb_optimizer_get_blog_option_' . $full_option_name, $value, $blog_id);
  }
}

if ( ! function_exists('sb_update_blog_option') ) {
  /**
   * Update blog option.
   *
   * @param $blog_id
   * @param $option_name
   * @param $value
   * @param bool $assert_update
   *
   * @return mixed
   */
  function sb_update_blog_option($blog_id, $option_name, $value, $assert_update = true) {
    $full_option_name = sb_get_option_name($option_name);
    $result = update_blog_option($blog_id, $full_option_name, $value);
    if ( $assert_update && ! $result ) {
      $current_value = sb_get_blog_option($blog_id, $option_name);
      return ( $current_value == $value );
    }
    return true;
  }
}

if ( ! function_exists('sb_ajax_user_allowed') ) {
  /**
   * Check if current user has capability, abort if not.
   *
   * @param bool $return_result
   * @param string $capability
   *
   * @return mixed
   */
  function sb_ajax_user_allowed($return_result = false, $capability = 'manage_options') {
    $user_can = apply_filters('sb_optimizer_ajax_user_allowed', current_user_can($capability));
    if ( $return_result ) {
      return $user_can;
    }
    if ( ! $user_can ) {
      wp_die();
    }
  }
}

if ( ! function_exists('sb_iterate_sites') ) {
  /**
   * Execute function closure for each site in multisite-network.
   *
   * @param $function
   *
   * @return bool
   */
  function sb_iterate_sites($function) {
    if ( ! is_multisite() ) return false;
    $sites = sb_get_sites();
    if ( is_array($sites) ) {
      foreach ($sites as $site) {
        $function($site);
      }
      return true;
    }
    return false;
  }
}

if ( ! function_exists('sb_count_sites') ) {
  /**
   * Count sites in multisite-network.
   *
   * @return int
   */
  function sb_count_sites() {
    $sites = sb_get_sites();
    return is_array($sites) ? count($sites) : 0;
  }
}

if ( ! function_exists('sb_get_sites') ) {
  /**
   * Get all sites in multisite-network.
   *
   * @return mixed|void
   */
  function sb_get_sites() {
    return apply_filters('sb_optimizer_site_iteration', get_sites());
  }
}

if ( ! function_exists('sb_require_superadmin') ) {
  /**
   * Require the user to be a super admin.
   */
  function sb_require_superadmin() {
    if ( ! is_multisite() || ! is_super_admin() ) {
      wp_die();
    }
  }
}

if ( ! function_exists('sb_get_blog_name') ) {
  /**
   * Get blog name.
   *
   * @param $blog_id
   *
   * @return bool|string
   */
  function sb_get_blog_name($blog_id) {
    $current_blog_details = get_blog_details( [ 'blog_id' => $blog_id ] );
    return $current_blog_details ? $current_blog_details->blogname : false;
  }
}

if ( ! function_exists('sb_delete_blog_option') ) {
  /**
   * Delete blog option.
   *
   * @param $blog_id
   * @param $option
   * @param bool $default
   *
   * @return mixed
   */
  function sb_delete_blog_option($blog_id, $option, $default = false) {
    return delete_blog_option($blog_id, sb_get_option_name($option), $default);
  }
}

if ( ! function_exists('sb_get_next_cron_time') ) {
  /**
   * Find out next time a cron event will be triggered.
   *
   * @param $cron_name
   *
   * @return bool|int|string
   */
  function sb_get_next_cron_time($cron_name) {
    foreach(  _get_cron_array() as $timestamp => $crons ){
      if ( in_array( $cron_name, array_keys( $crons ) ) ) {
        return $timestamp;
      }
    }
    return false;
  }
}

if ( ! function_exists('sb_is_debug') ) {
  /**
   * Whether we are in debug mode.
   *
   * @return bool
   */
  function sb_is_debug() {
    return ( defined('WP_DEBUG') && WP_DEBUG === true ) || ( array_key_exists('debug', $_GET) );
  }
}

if ( ! function_exists('sb_is_dev_debug') ) {
  /**
   * Check whether we are in Servebolt developer debug mode.
   *
   * @return bool
   */
  function sb_is_dev_debug() {
    return ( defined('SB_DEBUG') && SB_DEBUG === true ) || ( array_key_exists('debug', $_GET ) );
  }
}

if ( ! function_exists('sb_deactivate_plugin') ) {
    /**
     * Plugin deactivation.
     */
    function sb_deactivate_plugin() {
        sb_clear_all_cookies();
    }
}

if ( ! function_exists('sb_activate_plugin') ) {
    /**
     * Plugin activation.
     */
    function sb_activate_plugin() {
        sb_check_all_cookies();
    }
}

if ( ! function_exists('sb_check_all_cookies') ) {
    /**
     * Check the cookies we have been set.
     */
    function sb_check_all_cookies() {
        if ( ! class_exists('Servebolt_Nginx_FPC_Auth_Handling') ) {
            require_once SERVEBOLT_PATH . 'classes/nginx-fpc/sb-nginx-fpc-auth-handling.php';
        }
        ( new Servebolt_Nginx_FPC_Auth_Handling )->cache_cookie_check();
    }
}

if ( ! function_exists('sb_clear_all_cookies') ) {
    /**
     * Clean the cookies we have been settin'.
     */
    function sb_clear_all_cookies() {
        if ( ! class_exists('Servebolt_Nginx_FPC_Auth_Handling') ) {
            require_once SERVEBOLT_PATH . 'classes/nginx-fpc/sb-nginx-fpc-auth-handling.php';
        }
        ( new Servebolt_Nginx_FPC_Auth_Handling )->clear_no_cache_cookie();
    }
}

if ( ! function_exists('sb_delete_all_settings') ) {
  /**
   * Delete plugin settings.
   *
   * @param bool $all_sites
   */
  function sb_delete_all_settings($all_sites = true) {
    $option_names = [

      // General settings
      'asset_auto_version',
      'use_native_js_fallback',

      // Wipe nonce
      'ajax_nonce',
      'record_max_num_pages_nonce',

      // Wipe encryption keys
      'mcrypt_key',
      'openssl_key',
      'openssl_iv',

      // Wipe Cloudflare-related options
      'cf_switch',
      'cf_zone_id',
      'cf_auth_type',
      'cf_email',
      'cf_api_key',
      'cf_api_token',
      'cf_items_to_purge',
      'cf_cron_purge',

      // Wipe SB FPC-relateds options
      'fpc_switch',
      'fpc_settings',
      'fpc_exclude',
    ];
    foreach ( $option_names as $option_name ) {
      if ( is_multisite() && $all_sites ) {
        sb_iterate_sites(function ( $site ) use ($option_name) {
          sb_delete_blog_option($site->blog_id, $option_name);
        });
      } else {
        sb_delete_option($option_name);
      }
    }
  }
}

if ( ! function_exists('sb_natural_language_join') ) {
  /**
   * Join strings together in a natural readable way.
   *
   * @param array $list
   * @param string $conjunction
   * @param string $quotes
   *
   * @return string
   */
  function sb_natural_language_join(array $list, $conjunction = 'and', $quotes = '"') {
    $last = array_pop($list);
    if ($list) {
      return $quotes . implode($quotes . ', ' . $quotes, $list) . '" ' . $conjunction . ' ' . $quotes . $last . $quotes;
    }
    return $quotes . $last . $quotes;
  }
}

if ( ! function_exists('dd') ) {
	/**
	 * Die and dump.
	 *
	 * @param $var
	 */
	function dd($var) {
		if ( is_array($var) || is_object($var) ) {
			print_r($var);
		} else {
			var_dump($var);
		}
		exit;
	}
}

if ( ! function_exists('sb_format_array_to_csv') ) {
  /**
   * Convert an array to a CSV-string.
   *
   * @param $array
   * @param string $glue
   *
   * @return string
   */
  function sb_format_array_to_csv($array, $glue = ',') {
    return implode($glue, $array);
  }
}

if ( ! function_exists('fpc_exclude_post_table_row_markup') ) {
  /**
   * Build markup for row in FPC post exclude table.
   *
   * @param $post_id
   * @param bool $echo
   *
   * @return false|string
   */
  function fpc_exclude_post_table_row_markup($post_id, $echo = true) {
    if ( is_numeric($post_id) && $post = get_post($post_id) ) {
        $title = get_the_title($post_id);
        $url = get_permalink($post_id);
          $edit_url = get_edit_post_link($post_id);
          $is_post = true;
      } else {
        $title = false;
        $url = false;
          $is_post = false;
      }
    ob_start();
    ?>
      <tr class="exclude-item">
        <th scope="row" class="check-column">
          <label class="screen-reader-text" for="cb-select-<?php echo $post_id; ?>">Select "<?php echo $is_post ? $title : $url; ?>"</label>
          <input type="hidden" class="exclude-item-input" value="<?php echo esc_attr($post_id); ?>">
          <input id="cb-select-<?php echo $post_id; ?>" type="checkbox">
        </th>
          <?php if ( $is_post ) : ?>
        <td class="column-post-id has-row-actions fpc-exclude-item-column">
              <?php echo $post_id; ?>
              <div class="row-actions">
                <span class="trash"><a href="#" class="sb-remove-item-from-fpc-post-exclude"><?php sb_e('Delete'); ?></a> | </span>
                <span class="view"><a href="<?php echo esc_attr($url); ?>" target="_blank"><?php sb_e('View'); ?></a><?php if ( $edit_url ) echo ' | '; ?></span>
                <?php if ( $edit_url ) : ?>
                    <span class="view"><a href="<?php echo $edit_url; ?>" target="_blank"><?php sb_e('Edit'); ?></a></span>
                <?php endif; ?>
              </div>
            </td>
            <td class="fpc-exclude-item-column"><strong><?php echo $title; ?></strong></td>
          <?php else : ?>
            <td class="column-post-id has-row-actions fpc-exclude-item-column" colspan="2">
              <?php echo $post_id; ?> (<?php sb_e('Post does not exist.') ?>)
              <div class="row-actions">
                <span class="trash"><a href="#" class="sb-remove-item-from-fpc-post-exclude"><?php sb_e('Delete'); ?></a></span>
              </div>
            </td>
          <?php endif; ?>
        <td class="column-url" style="padding-left: 0;padding-top: 10px;padding-bottom: 10px;">
            <?php if ( $url ) : ?>
              <a href="<?php echo esc_attr($url); ?>" target="_blank"><?php echo $url; ?></a>
            <?php else: ?>
              <?php echo $url; ?>
            <?php endif; ?>

        </td>
      </tr>
    <?php
    $html = ob_get_contents();
    ob_end_clean();
    if ( ! $echo ) {
      return $html;
    }
    echo $html;
  }
}

if ( ! function_exists('sb_create_li_tags_from_array') ) {
  /**
   * Create li-tags from array.
   *
   * @param $iterator
   * @param $closure
   * @param bool $include_ul
   *
   * @return string
   */
  function sb_create_li_tags_from_array($iterator, $closure = false, $include_ul = true) {
    $markup = '';
    if ( $include_ul ) $markup .= '<ul>';
    array_map(function($item) use (&$markup, $closure) {
      $markup .= '<li>' . ( is_callable($closure) ? $closure($item) : $item ) . '</li>';
    }, $iterator);
    if ( $include_ul ) $markup .= '</ul>';
    return $markup;
  }
}

if ( ! function_exists('sb_display_value') ) {
    /**
     * Display value, regardless of type.
     *
     * @param $value
     * @param bool $return
     * @return bool|false|string|null
     */
    function sb_display_value($value, $return = false) {
        if ( is_bool($value) ) {
            $value = sb_boolean_to_string($value);
        } elseif ( is_string($value) ) {
            $value = $value;
        } else {
            ob_start();
            var_dump($value);
            $value = ob_get_contents();
            ob_end_clean();
        }
        if ( $return ) {
            return $value;
        }
        echo $value;
    }
}

if ( ! function_exists('sb_format_comma_string') ) {
  /**
   * Format a string with comma separated values.
   *
   * @param $string Comma separated values.
   *
   * @return array
   */
  function sb_format_comma_string($string) {
    $string = trim($string);
    if ( empty($string) ) {
      return [];
    }
    $array = explode(',', $string);
    $array = array_map(function ($item) {
      return trim($item);
    }, $array);
    return array_filter($array, function ($item) {
      return ! empty($item);
    });
  }
}

if ( ! function_exists('sb_write_log') ) {
  /**
   * Write to log.
   *
   * @param $log
   */
  function sb_write_log($log)  {
    if ( ! defined('WP_DEBUG') || WP_DEBUG == false ) return;
    if ( is_array( $log ) || is_object( $log ) ) {
      error_log( print_r( $log, true ) );
    } else {
      error_log( $log );
    }
  }
}

if ( ! function_exists('sb_array_get') ) {
  /**
   * Get item from array.
   *
   * @param $key
   * @param $array
   * @param bool $default_value
   *
   * @return bool
   */
  function sb_array_get($key, $array, $default_value = false) {
    return array_key_exists($key, $array) ? $array[$key] : $default_value;
  }
}
