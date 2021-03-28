<?php

namespace Servebolt\Optimizer\FullPageCache;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Traits\Singleton;

/**
 * Class FullPageCacheAuthHandling
 * @package Servebolt\Optimizer\FullPageCache
 *
 * This class will handle the "no_cache"-cookie. The cookie will be set when logged in and will result in Nginx cache being disabled for the authenticated user.
 *
 * Note: Cloudflare seems to respect this header also - so yeah, nice to know about when debugging.
 */
class FullPageCacheAuthHandling
{
    use Singleton;

	/**
	 * A property to store the cookie secure state for the login and "no-cache"-cookie.
	 *
	 * @var null
	 */
	private $secureLoggedInCookie = null;

	/**
	 * The cookie-name used to defined no-cache feature.
	 *
	 * @var string
	 */
	private $noCacheCookieName = 'no_cache';

    public static function init(): void
    {
        self::getInstance();
    }

    /**
     * FullPageCacheAuthHandling constructor.
     */
	public function __construct()
    {

		// When logging in then capture cookie secure state to use with the "no-cache"-cookie
		add_filter('secure_logged_in_cookie', [$this, 'fetchSecureLoggedInCookieValue'], PHP_INT_MAX, 1);

		// Set the "no-cache"-cookie on successful login
		add_action('set_auth_cookie', [$this, 'setNoCacheCookieAfterAuthentication'], 10, 0);

		// Remove "no-cache"-cookie if no longer logged in
		add_filter('init', [$this, 'noCacheCookieCheck']);

		// Clear no-cache cookie when logging out
		add_action('clear_auth_cookie', [$this, 'clearNoCacheCookie']);

	}

	/**
	 * Set "no_cache"-cookie. Will abort if cookie is already set, or if non existent while trying to invalidate the cookie.
	 *
	 * @param bool $state
	 */
	private function setNoCacheCookie(bool $state = true)
    {
		if ($state === false && ! $this->cookieIsSet()) {
            return;
        }
		$expire = $state ? 0 : (time() - YEAR_IN_SECONDS);
		setcookie($this->noCacheCookieName, 1, $expire, COOKIEPATH, COOKIE_DOMAIN, $this->cookieIsSecure(), true);
	}

	/**
	 * Set "no_cache"-cookie right after authentication cookies have been set.
	 */
	public function setNoCacheCookieAfterAuthentication()
    {
		$this->setNoCacheCookie();
	}

	/**
	 * Make sure the cookie is set according to login state.
	 */
	public function noCacheCookieCheck()
    {
		if ($this->cookieIsSet() && !is_user_logged_in()) {
			$this->clearNoCacheCookie();
		}
	}

    /**
     * Make sure the cookie is set according to login state.
     */
    public function cacheCookieCheck()
    {
        if (!$this->cookieIsSet() && is_user_logged_in()) {
            $this->setNoCacheCookie();
        }
    }

	/**
	 * Clear no-cache cookie.
	 */
	public function clearNoCacheCookie()
    {
		$this->setNoCacheCookie(false);
	}

	/**
	 * Capture WP cookie settings into class property.
	 *
	 * @param $secureLoggedInCookie
	 *
	 * @return mixed
	 */
	public function fetchSecureLoggedInCookieValue($secureLoggedInCookie)
    {
		$this->secureLoggedInCookie = $secureLoggedInCookie;
		return $secureLoggedInCookie;
	}

	/**
	 * Check if "no_cache"-cookie is set.
	 *
	 * @return bool
	 */
	private function cookieIsSet(): bool
    {
		return array_key_exists($this->noCacheCookieName, $_COOKIE);
	}

	/**
	 * Check whether we should store the cookie safe or not.
	 *
	 * @return mixed
	 */
	private function cookieIsSecure()
    {

		// Check if we have "recorded" the parameter during login and reuse that one
		if ( is_bool($this->secureLoggedInCookie) ) {
			return $this->secureLoggedInCookie;
		}

		// Code collected from WP core files, function "wp_set_auth_cookie" in wp-includes/pluggable.php:819
		$userId = get_current_user_id();
		$secure = is_ssl();
		$secure = apply_filters('secure_auth_cookie', $secure, $userId);
		$secureLoggedInCookie = $secure && 'https' === parse_url(get_option('home'), PHP_URL_SCHEME);
        $secureLoggedInCookie = apply_filters('secure_logged_in_cookie', $secureLoggedInCookie, $userId, $secure);
		return $secureLoggedInCookie;
	}
}
