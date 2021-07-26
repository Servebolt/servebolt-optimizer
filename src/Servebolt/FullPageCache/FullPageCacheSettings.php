<?php

namespace Servebolt\Optimizer\FullPageCache;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Traits\Singleton;
use function Servebolt\Optimizer\Helpers\checkboxIsChecked;
use function Servebolt\Optimizer\Helpers\listenForCheckboxOptionChange;
use function Servebolt\Optimizer\Helpers\smartGetOption;
use function Servebolt\Optimizer\Helpers\smartUpdateOption;

/**
 * Class FullPageCacheSettings
 * @package Servebolt\Optimizer\FullPageCache
 */
class FullPageCacheSettings
{
    use Singleton;

    /**
     * Alias for "getInstance".
     */
    public static function init(): void
    {
        self::getInstance();
    }

    /**
     * FullPageCacheSettings constructor.
     */
    public function __construct()
    {
        $this->initSettingsActions();
        $this->acdStateActions();
    }

    /**
     * Trigger HTML Cache active state actions whenever a ACD activate state action is triggered.
     */
    private function acdStateActions(): void
    {
        add_action('sb_optimizer_acd_enable', function () {
            do_action('sb_optimizer_html_cache_enable');
        });
        add_action('sb_optimizer_acd_disable', function () {
            do_action('sb_optimizer_html_cache_disable');
        });
    }

    /**
     * Add listeners for HTML Cache active state change.
     */
    private function initSettingsActions(): void
    {
        listenForCheckboxOptionChange(self::htmlCacheActiveOptionKey(), function($wasActive, $isActive, $optionName) {
            if ($isActive) {
                do_action('sb_optimizer_html_cache_enable');
            } else {
                do_action('sb_optimizer_html_cache_disable');
            }
        });
    }

    /**
     * Check if full page caching is active with optional blog check.
     *
     * @param null|int $blogId
     *
     * @return bool
     */
    public static function htmlCacheIsActive(?int $blogId = null): bool
    {
        return (bool) apply_filters('sb_optimizer_html_cache_is_active', checkboxIsChecked(smartGetOption($blogId, self::htmlCacheActiveOptionKey())));
    }

    /**
     * Check whether we have overridden the active status for HTML Cache.
     *
     * @return bool
     */
    public static function htmlCacheActiveStateIsOverridden(): bool
    {
        return has_filter('sb_optimizer_html_cache_is_active');
    }

    /**
     * The option name/key we use to store the active state for the HTML Cache.
     *
     * @return string
     */
    private static function htmlCacheActiveOptionKey(): string
    {
        return 'fpc_switch';
    }

    /**
     * Set full page caching is active/inactive, either for current blog or specified blog.
     *
     * @param bool $state
     * @param null|int $blogId
     *
     * @return bool|mixed
     */
    public static function htmlCacheToggleActive(bool $state, ?int $blogId = null)
    {
        return smartUpdateOption($blogId, self::htmlCacheActiveOptionKey(), $state);
    }
}
