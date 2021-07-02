<?php

namespace Servebolt\Optimizer\MenuCache;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Traits\Singleton;
use function Servebolt\Optimizer\Helpers\checkboxIsChecked;
use function Servebolt\Optimizer\Helpers\smartGetOption;

/**
 * Class MenuCache
 * @package Servebolt\Optimizer\MenuCache
 */
class WpMenuCache
{
    use Singleton;

    /**
     * Alias for "getInstance".
     */
    public static function init()
    {
        self::getInstance();
    }

    /**
     * MenuCache constructor.
     */
    public function __construct()
    {
        if (self::disabledForAuthenticatedUsers()) {
            add_filter('sb_optimizer_menu_cache_disabled_for_unauthenticated_users', '__return_true');
        }
        MenuCache::init();
    }

    /**
     * Check if feature is active.
     *
     * @param int|null $blogId
     * @return bool
     */
    public static function isActive(?int $blogId = null): bool
    {
        return checkboxIsChecked(smartGetOption($blogId, 'menu_cache_switch'));
    }

    /**
     * Check if we should only cache for authenticated users.
     *
     * @param int|null $blogId
     * @return bool
     */
    public static function disabledForAuthenticatedUsers(?int $blogId = null): bool
    {
        return checkboxIsChecked(smartGetOption($blogId, 'menu_cache_disabled_for_authenticated_switch'));
    }
}
