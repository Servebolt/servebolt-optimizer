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
 * Get a link to the Servebolt admin panel.
 *
 * @return bool|string
 */
function sb_get_admin_url() {
	$webroot_path = get_home_path();
	//$webroot_path = '/kunder/serveb_1234/custom_1234/public'; // For testing
	return ( preg_match( "@kunder/[a-z_0-9]+/[a-z_]+(\d+)/@", $webroot_path, $matches ) ) ? 'https://admin.servebolt.com/siteredirect/?site='. $matches[1] : false;
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
 * Create a Cloudflare_Error-instance.
 *
 * @param $exception
 *
 * @return Cloudflare_Error
 */
function sb_cf_error($exception) {
	return new Cloudflare_Error($exception->getMessage());
}

/**
 * Check whether a variable is a Cloudflare_Error-instance.
 *
 * @param $object
 *
 * @return bool
 */
function sb_is_error($object) {
	return is_object($object) && is_a($object, 'Cloudflare_Error');
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
 *
 * @return mixed|string|void
 */
function sb_generate_random_permanent_key($name) {
	$key = sb_get_option($name);
	if ( ! $key ) {
		$key = sb_generate_random_string(36);
		sb_update_option($name, $key);
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
	$value = apply_filters('sb_get_option_' . $full_option_name, $value);
	return $value;
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
		return ( get_option($option_name) == $value );
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
 * @param $id
 * @param $option
 * @param bool $default
 *
 * @return mixed
 */
function sb_get_blog_option($id, $option, $default = false) {
	return get_blog_option($id, sb_get_option_name($option), $default);
}

/**
 * Delete blog option.
 *
 * @param $id
 * @param $option
 * @param bool $default
 *
 * @return mixed
 */
function sb_delete_blog_option($id, $option, $default = false) {
	return get_blog_option($id, sb_get_option_name($option), $default);
}

/**
 * Update blog option.
 *
 * @param $id
 * @param $option
 * @param $value
 *
 * @return mixed
 */
function sb_update_blog_option($id, $option, $value) {
	return update_blog_option($id, sb_get_option_name($option), $value);
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
	return ( defined('WP_DEBUG') && WP_DEBUG === true ) || ( array_key_exists('debug', $_GET) && filter_var($_GET['debug'], FILTER_VALIDATE_BOOLEAN) === true );
}

/**
 * Check whether we are in Servebolt developer debug mode.
 *
 * @return bool
 */
function sb_is_dev_debug() {
	return ( defined('SB_DEBUG') && SB_DEBUG === true ) || ( array_key_exists('debug', $_GET) && filter_var($_GET['debug'], FILTER_VALIDATE_BOOLEAN) === true );
}

/**
 * Delete plugin settings.
 */
function sb_clear_all_settings() {
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
			foreach ( get_sites() as $site ) {
				sb_delete_blog_option($site->blog_id, $option_name);
			}
		}
		sb_delete_option($option_name);
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
 * Format a string with comma separated values.
 *
 * @param $string Comma separated values.
 *
 * @return array
 */
function sb_format_comma_string($string) {
	$array = explode(',', $string);
	$array = array_map(function ($item) {
		return trim($item);
	}, $array);
	return array_filter($array, function ($item) {
		return ! empty($item);
	});
}

/**
 * Class SB_Crypto
 */
class SB_Crypto {

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
	 *
	 * @return bool|string
	 */
	public static function encrypt($input_string, $method = false) {
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
	 * @param bool $method
	 *
	 * @return bool|string
	 */
	public static function decrypt($input_string, $method = false) {
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
		return sb_generate_random_permanent_key('mcrypt_key');
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
		$key = sb_generate_random_permanent_key('openssl_key');
		$iv = sb_generate_random_permanent_key('openssl_iv');
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
