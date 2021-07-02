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
        if (self::onlyForAuthenticatedUsers()) {
            add_filter('sb_optimizer_menu_cache_only_for_unauthenticated_users', '__return_true');
        }
        MenuCache::init();
    }

    /**
     * @param int|null $blogId
     * @return bool
     */
    public static function isActive(?int $blogId = null): bool
    {
        return checkboxIsChecked(smartGetOption($blogId, 'menu_cache_switch'));
    }

    /**
     * @param int|null $blogId
     * @return bool
     */
    public static function onlyForAuthenticatedUsers(?int $blogId = null): bool
    {
        return checkboxIsChecked(smartGetOption($blogId, 'menu_cache_only_authenticated_switch'));
    }
}
