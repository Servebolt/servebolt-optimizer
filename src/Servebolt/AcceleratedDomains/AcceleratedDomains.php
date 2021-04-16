<?php

namespace Servebolt\Optimizer\AcceleratedDomains;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Traits\Singleton;
use function Servebolt\Optimizer\Helpers\checkboxIsChecked;
use function Servebolt\Optimizer\Helpers\getOptionName;
use function Servebolt\Optimizer\Helpers\smartGetOption;
use function Servebolt\Optimizer\Helpers\smartUpdateOption;

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
        $this->activeStateEvent();
        $this->cachePurgeDriverLockWhenAcdActive();
        $this->htmlCacheActiveLockWhenAcdActive();
    }

    /**
     * Add listeners for ACD active state change.
     */
    private function activeStateEvent(): void
    {
        add_filter('pre_update_option_' . getOptionName('acd_switch'), [$this, 'detectAcdActivation'], 10, 2);
    }

    /**
     * Detect when ACD gets activated/deactivated.
     *
     * @param $newValue
     * @param $oldValue
     * @return mixed
     */
    function detectAcdActivation($newValue, $oldValue)
    {
        $wasActive = filter_var($oldValue, FILTER_VALIDATE_BOOLEAN);
        $isActive = filter_var($newValue, FILTER_VALIDATE_BOOLEAN);
        $didChange = $wasActive !== $isActive;
        if ($didChange) {
            if ($isActive) {
                do_action('sb_optimizer_acd_enable');
            } else {
                do_action('sb_optimizer_acd_disable');
            }
        }
        return $newValue;
    }

    /**
     * @param bool $state
     * @param int|null $blogId
     */
    public static function htmlMinifyToggleActive(bool $state, ?int $blogId = null): void
    {
        smartUpdateOption($blogId, 'acd_minify_switch', $state);
    }

    /**
     * @param bool $state
     * @param int|null $blogId
     */
    public static function toggleActive(bool $state, ?int $blogId = null): void
    {
        smartUpdateOption($blogId, 'acd_switch', $state);
    }

    /**
     * Check whether the cache purge feature is active.
     *
     * @param int|null $blogId
     * @return bool
     */
    public static function isActive(?int $blogId = null): bool
    {
        return checkboxIsChecked(smartGetOption($blogId, 'acd_switch'));
    }

    /**
     * Check whether the cache purge feature is active.
     *
     * @param int|null $blogId
     * @return bool
     */
    public static function htmlMinifyIsActive(?int $blogId = null): bool
    {
        return checkboxIsChecked(smartGetOption($blogId, 'acd_minify_switch'));
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
