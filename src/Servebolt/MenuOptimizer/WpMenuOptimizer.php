<?php

namespace Servebolt\Optimizer\MenuOptimizer;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Traits\Singleton;
use function Servebolt\Optimizer\Helpers\checkboxIsChecked;
use function Servebolt\Optimizer\Helpers\isFrontEnd;
use function Servebolt\Optimizer\Helpers\smartGetOption;

/**
 * Class WpMenuOptimizer
 * @package Servebolt\Optimizer\MenuOptimizer
 */
class WpMenuOptimizer
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
     * WpMenuOptimizer constructor.
     */
    public function __construct()
    {
        if (self::disabledForAuthenticatedUsers()) {
            add_filter('sb_optimizer_menu_cache_disabled_for_unauthenticated_users', '__return_true');
        }
        if (isFrontEnd()) {
            add_action('init', __NAMESPACE__ . '\\MenuOptimizer::init');
        }
        add_action('admin_init', __NAMESPACE__ . '\\MenuOptimizerCachePurge::init');
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
