<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Get a link to the Servebolt admin panel.
 *
 * @return bool|string
 */
function get_sb_admin_url() {
	$webroot_path = get_home_path();
	return ( preg_match( "@kunder/[a-z_0-9]+/[a-z_]+(\d+)/@", $webroot_path, $matches ) ) ? 'https://admin.servebolt.com/siteredirect/?site='. $matches[1] : false;
}



/**
 * Get Servebolt_Performance_Checks-instance.
 *
 * @return Servebolt_Performance_Checks|null
 */
function sb_performance_checks() {
	require_once SERVEBOLT_PATH . 'admin/performance-checks.php';
	return Servebolt_Performance_Checks::instance();
}

/**
 * Get Servebolt_Checks-instance.
 *
 * @return Servebolt_Optimize_DB|null
 */
function sb_checks() {
	require_once SERVEBOLT_PATH . 'admin/optimize-db/checks.php';
	return Servebolt_Checks::instance();
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
	return true;
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
	return sprintf('servebolt_%s', $option);
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
 * Delete custom settings.
 */
function sb_clear_all_settings() {
	foreach(['settings'] as $key) {
		$option_name = get_options_name($key);
		\delete_option($option_name);
		\delete_site_option($option_name);
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
