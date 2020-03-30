<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Get Servebolt_Performance_Checks-instance.
 *
 * @return Servebolt_Performance_Checks|null
 */
function sb_performance_checks() {
	require_once SERVEBOLT_PATH . 'admin/performance-checks.php';
	return Servebolt_Performance_Checks::get_instance();
}

/**
 * Get Servebolt_Optimize_DB-instance.
 *
 * @return Servebolt_Performance_Checks|null
 */
function sb_optimize_db() {
	require_once SERVEBOLT_PATH . 'admin/optimize-db/optimize-db.php';
	return Servebolt_Optimize_DB::get_instance();
}

/**
 * Get Servebolt_Checks-instance.
 *
 * @return Servebolt_Checks|null
 */
function sb_cf() {
	require_once SERVEBOLT_PATH . 'classes/cloudflare/sb-cf.php';
	return Servebolt_CF::get_instance();
}

/**
 * Get Servebolt_Checks-instance.
 *
 * @return Servebolt_Checks|null
 */
function sb_cf_cache_controls() {
	require_once SERVEBOLT_PATH . 'admin/cf-cache-controls.php';
	return CF_Cache_Controls::get_instance();
}

/**
 * Get Nginx_FPC_Controls-instance.
 *
 * @return Servebolt_Nginx_FPC|null
 */
function sb_nginx_fpc_controls() {
	require_once SERVEBOLT_PATH . 'admin/nginx-fpc-controls.php';
	return Nginx_FPC_Controls::get_instance();
}

/**
 * Get Servebolt_Nginx_FPC-instance.
 *
 * @return Servebolt_Nginx_FPC|null
 */
function sb_nginx_fpc() {
	require_once SERVEBOLT_PATH . 'classes/nginx-fpc/sb-nginx-fpc.php';
	return Servebolt_Nginx_FPC::get_instance();
}

/**
 * Get Servebolt_Checks-instance.
 *
 * @return Servebolt_Optimize_DB|null
 */
function sb_checks() {
	require_once SERVEBOLT_PATH . 'admin/optimize-db/checks.php';
	return Servebolt_Checks::get_instance();
}

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

/**
 * Check if we are running as CLI.
 * s
 * @return bool
 */
function sb_is_cli() {
	return ( defined( 'WP_CLI' ) && WP_CLI );
}

/**
 * Get a link to the Servebolt admin panel.
 *
 * @return bool|string
 */
function sb_get_admin_url() {
	$web_root_path = sb_is_dev_debug() ? '/kunder/serveb_1234/custom_4321/public' : get_home_path();
	return ( preg_match( "@kunder/[a-z_0-9]+/[a-z_]+(\d+)/@", $web_root_path, $matches ) ) ? 'https://admin.servebolt.com/siteredirect/?site='. $matches[1] : false;
}

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

/**
 * Get plugin text domain.
 *
 * @return string
 */
function sb_get_text_domain() {
	return 'servebolt-wp';
}

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

/**
 * Translate function wrapper.
 *
 * @param $string
 * @param null $domain
 */
function sb_e($string, $domain = null) {
	_e($string, $domain ?: sb_get_text_domain());
}

/**
 * Check if the site is hosted on Servebolt.com.
 *
 * @return bool
 */
function host_is_servebolt() {
	if ( defined('HOST_IS_SERVEBOLT_OVERRIDE') && is_bool(HOST_IS_SERVEBOLT_OVERRIDE) ) return HOST_IS_SERVEBOLT_OVERRIDE;
	foreach(['SERVER_ADMIN', 'SERVER_NAME'] as $key) {
		if (array_key_exists($key, $_SERVER)) {
			$check = $_SERVER[$key];
			if (strpos($check, 'raskesider.no') !== false || strpos($check, 'servebolt.com') !== false ){
				return true;
			}
		}
	}
	return false;
}

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

/**
 * Get ajax nonce.
 */
function sb_get_ajax_nonce() {
	return wp_create_nonce(sb_get_ajax_nonce_key());
}

/**
 * Get ajax nonce key, generate one if it does not exists.
 *
 * @return mixed|string|void
 */
function sb_get_ajax_nonce_key() {
	return sb_generate_random_permanent_key('ajax_nonce');
}

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
		return ( sb_get_option($option_name) == $value );
	}
	return true;
}

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
		return ( sb_get_blog_option($blog_id, $option_name) == $value );
	}
	return true;
}

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

/**
 * Count sites in multisite-network.
 *
 * @return int
 */
function sb_count_sites() {
	$sites = sb_get_sites();
	return is_array($sites) ? count($sites) : 0;
}

/**
 * Get all sites in multisite-network.
 *
 * @return mixed|void
 */
function sb_get_sites() {
	return apply_filters('sb_optimizer_site_iteration', get_sites());
}

/**
 * Require the user to be a super admin.
 */
function sb_require_superadmin() {
	if ( ! is_multisite() || ! is_super_admin() ) {
		wp_die();
	}
}

/**
 * Get blog name.
 *
 * @param $blog_id
 */
function sb_get_blog_name($blog_id) {
	$current_blog_details = get_blog_details( [ 'blog_id' => $blog_id ] );
	return $current_blog_details ? $current_blog_details->blogname : false;
}

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

/**
 * Whether we are in debug mode.
 *
 * @return bool
 */
function sb_is_debug() {
	return ( defined('WP_DEBUG') && WP_DEBUG === true ) || ( array_key_exists('debug', $_GET) );
}

/**
 * Check whether we are in Servebolt developer debug mode.
 *
 * @return bool
 */
function sb_is_dev_debug() {
	return ( defined('SB_DEBUG') && SB_DEBUG === true ) || ( array_key_exists('debug', $_GET ) );
}

/**
 * Delete plugin settings.
 */
function sb_delete_all_settings() {
	$option_names = [
		'ajax_nonce',
		'mcrypt_key',
		'openssl_key',
		'openssl_iv',

		'cf_switch',
		'cf_zone_id',
		'cf_auth_type',
		'cf_email',
		'cf_api_key',
		'cf_api_token',
		'cf_items_to_purge',
		'cf_cron_purge',

		'fpc_switch',
		'fpc_settings',
		'fpc_exclude'
	];
	foreach ( $option_names as $option_name ) {
		if ( is_multisite() ) {
			sb_iterate_sites(function ( $site ) use ($option_name) {
				sb_delete_blog_option($site->blog_id, $option_name);
			});
		} else {
			sb_delete_option($option_name);
		}
	}
}

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

/**
 * Create li-tags from array.
 *
 * @param $iterator
 * @param $closure
 * @param bool $include_ul
 *
 * @return string
 */
function create_li_tags_from_array($iterator, $closure = false, $include_ul = true) {
	$markup = '';
	if ( $include_ul ) $markup .= '<ul>';
	array_map(function($item) use (&$markup, $closure) {
		$markup .= '<li>' . ( is_callable($closure) ? $closure($item) : $item ) . '</li>';
	}, $iterator);
	if ( $include_ul ) $markup .= '</ul>';
	return $markup;
}

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

/**
 * Write to log.
 *
 * @param $log
 */
function sb_write_log($log)  {
	if ( is_array( $log ) || is_object( $log ) ) {
		error_log( print_r( $log, true ) );
	} else {
		error_log( $log );
	}
}

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

/**
 * Class SB_Crypto
 */
class SB_Crypto {

	/**
	 * Blog id - used to retrieve encryption keys for the given blog.
	 *
	 * @var bool
	 */
	private static $blog_id = false;

	/**
	 * Determine if and which encryption method is available.
	 *
	 * @return bool|string
	 */
	private static function determine_encryption_method() {
		if ( function_exists('openssl_encrypt') && function_exists('openssl_decrypt') ) {
			return 'openssl';
		}
		if ( function_exists('mcrypt_encrypt') && function_exists('mcrypt_decrypt') ) {
			return 'mcrypt';
		}
		return false;
	}

	/**
	 * Encrypt string.
	 *
	 * @param $input_string
	 * @param bool $method
	 * @param bool $blog_id
	 *
	 * @return bool|string
	 */
	public static function encrypt($input_string, $method = false, $blog_id = false) {
		if ( is_multisite() && is_numeric($blog_id) ) {
			self::$blog_id = $blog_id;
		}
		try {
			if ( ! $method ) {
				$method = self::determine_encryption_method();
			}
			switch ( $method ) {
				case 'openssl':
					return self::openssl_encrypt($input_string);
					break;
				case 'mcrypt':
					return self::mcrypt_encrypt($input_string);
					break;
			}
		} catch (Exception $e) {
			return false;
		}
		return false;
	}

	/**
	 * Decrypt string.
	 *
	 * @param $input_string

	 * @param bool $blog_id
   * @param bool $method
	 *
	 * @return bool|string
	 */
	public static function decrypt($input_string, $blog_id = false, $method = false) {
		if ( is_multisite() && is_numeric($blog_id) ) {
			self::$blog_id = $blog_id;
		}
		try {
			if ( ! $method ) {
				$method = self::determine_encryption_method();
			}
			switch ( $method ) {
				case 'openssl':
					return self::openssl_decrypt($input_string);
					break;
				case 'mcrypt':
						return self::mcrypt_decrypt($input_string);
					break;
			}
		} catch (Exception $e) {
			return false;
		}
		return false;
	}

	/**
	 * Mcrypt key.
	 *
	 * @return string
	 */
	public static function mcrypt_key() {
		return sb_generate_random_permanent_key('mcrypt_key', self::$blog_id);
	}

	/**
	 * Initiate mcrypt encryption/decryption.
	 *
	 * @return array
	 */
	public static function mcrypt_init() {
		$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
		$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
		$h_key = hash('sha256', self::mcrypt_key(), TRUE);
		return compact('iv', 'h_key');
	}

	/**
	 * Encrypt string using mcrypt.
	 *
	 * @param $input_string
	 *
	 * @return string
	 */
	public static function mcrypt_encrypt($input_string) {
		$init = self::mcrypt_init();
		return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $init['h_key'], $input_string, MCRYPT_MODE_ECB, $init['iv']));
	}

	/**
	 * Decrypt string using mcrypt.
	 *
	 * @param $encrypted_input_string
	 *
	 * @return string
	 */
	public static function mcrypt_decrypt($encrypted_input_string) {
		$init = self::mcrypt_init();
		return trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $init['h_key'], base64_decode($encrypted_input_string), MCRYPT_MODE_ECB, $init['iv']));
	}

	/**
	 * OpenSSL encryption keys.
	 *
	 * @return array
	 */
	public static function openssl_keys() {
		$key = sb_generate_random_permanent_key('openssl_key', self::$blog_id);
		$iv = sb_generate_random_permanent_key('openssl_iv', self::$blog_id);
		return compact('key', 'iv');
	}

	/**
	 * Init OpenSSL.
	 *
	 * @return array
	 */
	public static function openssl_init() {
		$encrypt_method = 'AES-256-CBC';
		$secret = self::openssl_keys();
		$key = hash('sha256', $secret['key']);
		$iv = substr(hash('sha256', $secret['iv']), 0, 16);
		return compact('encrypt_method', 'key', 'iv');
	}

	/**
	 * Encrypt string using mcrypt.
	 *
	 * @param $input_string
	 *
	 * @return string
	 */
	public static function openssl_encrypt($input_string) {
		$init = self::openssl_init();
		return base64_encode(openssl_encrypt($input_string, $init['encrypt_method'], $init['key'], 0, $init['iv']));
	}

	/**
	 * Decrypt string using OpenSSL.
	 *
	 * @param $encrypted_input_string
	 *
	 * @return string
	 */
	public static function openssl_decrypt($encrypted_input_string) {
		$init = self::openssl_init();
		return openssl_decrypt(base64_decode($encrypted_input_string), $init['encrypt_method'], $init['key'], 0, $init['iv']);
	}

}
