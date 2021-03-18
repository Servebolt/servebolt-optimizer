<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Class Servebolt_Nginx_FPC_Auth_Handling
 *
 * This class will handle the "no_cache"-cookie. The cookie will be set when logged in and will result in Nginx cache being disabled for the authenticated user.
 *
 * Note: Cloudflare seems to respect this header also - so yeah, nice to know about when debugging.
 */
class Servebolt_Nginx_FPC_Auth_Handling {

	/**
	 * A property to store the cookie secure state for the login and "no-cache"-cookie.
	 *
	 * @var null
	 */
	private $secure_logged_in_cookie = null;

	/**
	 * The cookie-name used to defined no-cache feature.
	 *
	 * @var string
	 */
	private $no_cache_cookie_name = 'no_cache';

	/**
	 * Servebolt_Nginx_FPC_No_Cache constructor.
	 */
	public function setup() {

		// When logging in then capture cookie secure state to use with the "no-cache"-cookie
		add_filter( 'secure_logged_in_cookie', [ $this, 'fetch_secure_logged_in_cookie_value' ], PHP_INT_MAX, 1 );

		// Set the "no-cache"-cookie on successful login
		add_action( 'set_auth_cookie', [ $this, 'set_no_cache_cookie_after_authentication' ], 10, 0 );

		// Remove "no-cache"-cookie if no longer logged in
		add_filter( 'init', [ $this, 'no_cache_cookie_check' ] );

		// Clear no-cache cookie when logging out
		add_action('clear_auth_cookie', [$this, 'clearNoCacheCookie']);

	}

	/**
	 * Set "no_cache"-cookie. Will abort if cookie is already set, or if non existent while trying to invalidate the cookie.
	 *
	 * @param bool $state
	 */
	private function set_no_cache_cookie(bool $state = true) {
		if ( $state === false && ! $this->cookie_is_set() ) return;
		$expire = $state ? 0 : ( time() - YEAR_IN_SECONDS );
		setcookie( $this->no_cache_cookie_name, 1, $expire, COOKIEPATH, COOKIE_DOMAIN, $this->cookie_is_secure(), true );
	}

	/**
	 * Set "no_cache"-cookie right after authentication cookies have been set.
	 */
	public function set_no_cache_cookie_after_authentication() {
		$this->set_no_cache_cookie();
	}

	/**
	 * Make sure the cookie is set according to login state.
	 */
	public function no_cache_cookie_check() {
		if ( $this->cookie_is_set() && ! is_user_logged_in() ) {
			$this->clearNoCacheCookie();
		}
	}

    /**
     * Make sure the cookie is set according to login state.
     */
    public function cache_cookie_check() {
        if ( ! $this->cookie_is_set() && is_user_logged_in() ) {
            $this->set_no_cache_cookie();
        }
    }

	/**
	 * Clear no-cache cookie.
	 */
	public function clearNoCacheCookie() {
		$this->set_no_cache_cookie(false);
	}

	/**
	 * Capture WP cookie settings into class property.
	 *
	 * @param $secure_logged_in_cookie
	 *
	 * @return mixed
	 */
	public function fetch_secure_logged_in_cookie_value($secure_logged_in_cookie) {
		$this->secure_logged_in_cookie = $secure_logged_in_cookie;
		return $secure_logged_in_cookie;
	}

	/**
	 * Check if "no_cache"-cookie is set.
	 *
	 * @return bool
	 */
	private function cookie_is_set() {
		return array_key_exists($this->no_cache_cookie_name, $_COOKIE);
	}

	/**
	 * Check whether we should store the cookie safe or not.
	 *
	 * @return mixed
	 */
	private function cookie_is_secure() {

		// Check if we have "recorded" the parameter during login and reuse that one
		if ( is_bool($this->secure_logged_in_cookie) ) {
			return $this->secure_logged_in_cookie;
		}

		// Code collected from WP core files, function "wp_set_auth_cookie" in wp-includes/pluggable.php:819
		$user_id = get_current_user_id();
		$secure = is_ssl();
		$secure = apply_filters( 'secure_auth_cookie', $secure, $user_id );
		$secure_logged_in_cookie = $secure && 'https' === parse_url( get_option( 'home' ), PHP_URL_SCHEME );
		$secure_logged_in_cookie = apply_filters( 'secure_logged_in_cookie', $secure_logged_in_cookie, $user_id, $secure );
		return $secure_logged_in_cookie;
	}

}
