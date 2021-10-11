<?php

namespace Servebolt\Optimizer\MenuOptimizer;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Traits\Singleton;
use function Servebolt\Optimizer\Helpers\checkboxIsChecked;
use function Servebolt\Optimizer\Helpers\isFrontEnd;
use function Servebolt\Optimizer\Helpers\setDefaultOption;
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
        $this->defaultOptionValues();
        if (self::disabledForAuthenticatedUsers()) {
            add_filter('sb_optimizer_menu_optimizer_disabled_for_unauthenticated_users', '__return_true');
        }
        if (isFrontEnd()) {
            add_action('init', __NAMESPACE__ . '\\MenuOptimizer::init');
        }
        add_action('admin_init', __NAMESPACE__ . '\\MenuOptimizerCachePurge::init');
    }

    /**
     * Set default option values.
     */
    private function defaultOptionValues(): void
    {
        setDefaultOption('menu_cache_auto_cache_purge_on_menu_update', '__return_true');
        setDefaultOption('menu_cache_auto_cache_purge_on_front_page_settings_update', '__return_true');
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

    /**
     * Check if we should automatically purge cache whenever menu is updated.
     *
     * @param int|null $blogId
     * @return bool
     */
    public static function automaticCachePurgeOnMenuChange(?int $blogId = null): bool
    {
        return checkboxIsChecked(smartGetOption($blogId, 'menu_cache_auto_cache_purge_on_menu_update'));
    }

    /**
     * Check if we should automatically purge cache whenever front page settings is updated.
     *
     * @param int|null $blogId
     * @return bool
     */
    public static function automaticCachePurgeOnFrontPageSettingsUpdate(?int $blogId = null): bool
    {
        return checkboxIsChecked(smartGetOption($blogId, 'menu_cache_auto_cache_purge_on_front_page_settings_update'));
    }

    /**
     * Check if we should run timing.
     *
     * @param int|null $blogId
     * @return bool
     */
    public static function runTiming(?int $blogId = null): bool
    {
        return checkboxIsChecked(smartGetOption($blogId, 'menu_cache_run_timing'));
    }

    /**
     * Check if we should use simple menu signature.
     *
     * @param int|null $blogId
     * @return bool
     */
    public static function useSimpleMenuSignature(?int $blogId = null): bool
    {
        return checkboxIsChecked(smartGetOption($blogId, 'menu_cache_simple_menu_signature'));
    }
}
