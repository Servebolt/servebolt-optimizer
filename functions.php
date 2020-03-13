<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Get a link to the Servebolt admin panel.
 *
 * @return bool|string
 */
function get_sb_admin_url() {
	$webroot_path = get_home_path();
	//$webroot_path = '/kunder/serveb_5418/bankfl_7205/public'; // For testing
	return ( preg_match( "@kunder/[a-z_0-9]+/[a-z_]+(\d+)/@", $webroot_path, $matches ) ) ? 'https://admin.servebolt.com/siteredirect/?site='. $matches[1] : false;
}

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
	require_once SERVEBOLT_PATH . 'classes/servebolt-nginx-fpc.class.php';
	return Servebolt_Nginx_FPC::get_instance();
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
 * Get Servebolt_Checks-instance.
 *
 * @return Servebolt_Optimize_DB|null
 */
function sb_checks() {
	require_once SERVEBOLT_PATH . 'admin/optimize-db/checks.php';
	return Servebolt_Checks::get_instance();
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
 * Get Servebolt_Checks-instance.
 *
 * @return Servebolt_Checks|null
 */
function sb_cf() {
	require_once SERVEBOLT_PATH . 'classes/servebolt-cf.class.php';
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
function remove_keys_from_array($arguments, $blacklisted_keys) {
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
	$key = sb_get_option('ajax_nonce');
	if ( ! $key ) {
		$key = uniqid() . uniqid() . uniqid();
		sb_update_option('ajax_nonce', $key);
	}
	return $key;
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
		$arguments = remove_keys_from_array($arguments, ['path', 'arguments', 'echo', 'html']);
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
 * @param $option
 * @param bool $default
 *
 * @return mixed|void
 */
function sb_get_option($option, $default = false) {
	return get_option(sb_get_option_name($option), $default);
}

/**
 * Update option.
 *
 * @param $key
 * @param $value
 * @param bool $assertUpdate
 *
 * @return bool
 */
function sb_update_option($key, $value, $assertUpdate = true) {
	$option_name = sb_get_option_name($key);
	$result = update_option($option_name, $value);
	if ( $assertUpdate && ! $result ) {
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
 * Delete site option.
 *
 * @param $option
 *
 * @return bool
 */
function sb_delete_site_option($option) {
	return delete_site_option(sb_get_option_name($option));
}

/**
 * Delete plugin settings.
 */
function sb_clear_all_settings() {
	$option_names = [
		'ajax_nonce',

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
				sb_get_blog_option($site->blog_id, $option_name);
			}
		}
		sb_delete_option($option_name);
		sb_delete_site_option($option_name);
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
