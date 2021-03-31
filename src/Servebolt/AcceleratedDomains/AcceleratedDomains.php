<?php

namespace Servebolt\Optimizer\AcceleratedDomains;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Traits\Singleton;
use function Servebolt\Optimizer\Helpers\checkboxIsChecked;
use function Servebolt\Optimizer\Helpers\getBlogOption;
use function Servebolt\Optimizer\Helpers\getOption;
use function Servebolt\Optimizer\Helpers\updateBlogOption;
use function Servebolt\Optimizer\Helpers\updateOption;

/**
 * Class AcceleratedDomains
 * @package Servebolt\Optimizer\AcceleratedDomains
 */
class AcceleratedDomains
{
    use Singleton;

    public static function init()
    {
        self::getInstance();
    }

    /**
     * AcceleratedDomains constructor.
     */
    public function __construct()
    {
        new AcceleratedDomainsHeaders;
        $this->cachePurgeDriverLockWhenAcdActive();
        $this->htmlCacheActiveLockWhenAcdActive();
    }

    /**
     * @param bool $state
     * @param int|null $blogId
     */
    public static function htmlMinifyToggleActive(bool $state, ?int $blogId = null): void
    {
        $key = 'acd_minify_switch';
        if (is_numeric($blogId)) {
            updateBlogOption($blogId, $key, $state);
        } else {
            updateOption($key, $state);
        }
    }

    /**
     * @param bool $state
     * @param int|null $blogId
     */
    public static function toggleActive(bool $state, ?int $blogId = null): void
    {
        $key = 'acd_switch';
        if (is_numeric($blogId)) {
            updateBlogOption($blogId, $key, $state);
        } else {
            updateOption($key, $state);
        }
    }

    /**
     * Check whether the cache purge feature is active.
     *
     * @param int|null $blogId
     * @return bool
     */
    public static function isActive(?int $blogId = null): bool
    {
        $key = 'acd_switch';
        if (is_numeric($blogId)) {
            $value = getBlogOption($blogId, $key);
        } else {
            $value = getOption($key);
        }
        return checkboxIsChecked($value);
    }

    /**
     * Check whether the cache purge feature is active.
     *
     * @param int|null $blogId
     * @return bool
     */
    public static function htmlMinifyIsActive(?int $blogId = null): bool
    {
        $key = 'acd_minify_switch';
        if (is_numeric($blogId)) {
            $value = getBlogOption($blogId, $key);
        } else {
            $value = getOption($key);
        }
        return checkboxIsChecked($value);
    }

    /**
     * Lock cache purge driver to ACD whenever ACD-feature is active.
     */
    private function cachePurgeDriverLockWhenAcdActive(): void
    {
        if (self::isActive()) {
            add_filter('sb_optimizer_selected_cache_purge_driver', function() {
                return 'acd';
            }, 10, 0);
        }
    }

    /**
     * Lock html cache as active whenever ACD-feature is active.
     */
    private function htmlCacheActiveLockWhenAcdActive(): void
    {
        if (self::isActive()) {
            add_filter('sb_optimizer_fpc_is_active', function() {
                return true;
            }, 10, 0);
        }
    }
}