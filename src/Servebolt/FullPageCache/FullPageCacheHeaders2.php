<?php

namespace Servebolt\Optimizer\FullPageCache;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Traits\Singleton;
use function Servebolt\Optimizer\Helpers\isFrontEnd;
use function Servebolt\Optimizer\Helpers\isHostedAtServebolt;

/**
 * Class FullPageCacheHeaders2
 * @package Servebolt
 */
class FullPageCacheHeaders
{
    use Singleton;

    /**
     * @var array
     */
    private $headers = [];

    /**
     * Alias for "getInstance".
     */
    public static function init(): void
    {
        self::getInstance();
    }

    /**
     * FullPageCacheHeaders2 constructor.
     */
    public function __construct()
    {
        if (isFrontEnd()) {
            add_action('wp_headers', [$this, 'alterHeaders']);
        }
    }

    /**
     * @param $headers
     * @return mixed
     */
    public function alterHeaders($headers)
    {
        $this->headers = $headers;
        $this->addCacheHeaders();
        return $this->headers;
    }

    /**
     * @return bool
     */
    private function printCacheHeadersEvenWhenInactive(): bool
    {
        if (isHostedAtServebolt()) {
            return true;
        }
        return false;
    }

    /**
     * Add cache headers.
     */
    private function addCacheHeaders(): void
    {
        if (
            !FullPageCacheSettings::fpcIsActive()
            || $this->isAuthenticatedUser()
        ) {
            if ($this->printCacheHeadersEvenWhenInactive()) {
                $this->noCacheHeaders();
            }
            return;
        }

        if ($this->shouldCacheBasedOnUrl()) {
            $this->cacheHeaders();
        } else {
            $this->noCacheHeaders();
        }
    }

    /**
     * Check whether a URL should be excluded from cache.
     *
     * @return bool
     */
    private function shouldCacheBasedOnUrl(): bool
    {
        global $wpdb;
        $currentUrl = $this->getCurrentUrl();
        $sql = $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}sb_cache_exception WHERE url = %s LIMIT 1", $currentUrl);
        return $wpdb->get_var($sql) == 0;
    }

    /**
     * Get current URL.
     *
     * @return string
     */
    private function getCurrentUrl(): string
    {
        global $wp;
        return trailingslashit(home_url(add_query_arg([], $wp->request)));
    }

    /**
     * Add cache headers.
     */
    private function cacheHeaders(): void
    {
        $this->addHeader('Cache', 'yes');
    }

    /**
     * Add no cache headers.
     */
    private function noCacheHeaders(): void
    {
        $this->addHeader('Cache', 'no');
    }

    /**
     * Add headers.
     *
     * @param array $headers
     */
    private function addHeaders(array $headers): void
    {
        foreach ($headers as $key => $value)
        {
            $this->addHeader($key, $value);
        }
    }

    /**
     * Add header.
     *
     * @param string $key
     * @param string $value
     */
    private function addHeader(string $key, string $value): void
    {
        $this->headers[$key] = $value;
    }

    /**
     * Check whether the user is authenticated.
     *
     * @return bool
     */
    private function isAuthenticatedUser(): bool
    {
        // Authentication check override
        $customAuthenticationCheck = apply_filters('sb_optimizer_cache_authentication_check', null);
        if (is_bool($customAuthenticationCheck)) {
            return $customAuthenticationCheck;
        }

        // Authenticated user check
        if (!is_user_logged_in()) {

            // User not authenticated
            return false;
        }

        // Handle roles that are just used for front-end authentication (subscribers, customers for WooCommerce etc.)
        $rolesNotConsideredAuthenticated = apply_filters('sb_optimizer_roles_not_considered_authenticated', [
            'subscriber',
            'customer',
        ]);
        $user = wp_get_current_user();
        foreach ($rolesNotConsideredAuthenticated as $role) {
            if (in_array($role, $user->roles)) {
                return false; // This user has a role that is not considered authenticated in regards to cache handling / logic
            }
        }

        // User is considered authentication
        return true;
    }
}
