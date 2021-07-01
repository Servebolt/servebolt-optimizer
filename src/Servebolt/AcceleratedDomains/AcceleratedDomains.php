<?php

namespace Servebolt\Optimizer\AcceleratedDomains;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\CachePurge\CachePurge;
use Servebolt\Optimizer\Traits\Singleton;
use Servebolt\Optimizer\AcceleratedDomains\ImageResize\AcceleratedDomainsImageResize;
use function Servebolt\Optimizer\Helpers\checkboxIsChecked;
use function Servebolt\Optimizer\Helpers\getOptionName;
use function Servebolt\Optimizer\Helpers\setOptionOverride;
use function Servebolt\Optimizer\Helpers\smartGetOption;
use function Servebolt\Optimizer\Helpers\smartUpdateOption;

/**
 * Class AcceleratedDomains
 * @package Servebolt\Optimizer\AcceleratedDomains
 */
class AcceleratedDomains
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
     * AcceleratedDomains constructor.
     */
    public function __construct()
    {
        new AcceleratedDomainsHeaders;
        new AcceleratedDomainsSettings;

        if (AcceleratedDomainsImageResize::hasAccessToFeature()) {
            new AcceleratedDomainsImageResize;
        }

        $this->disableApoWhenAcdActive();
        $this->activateCachePurgeFeatureOnAcdActivation();
        $this->cachePurgeDriverLockWhenAcdActive();
        $this->htmlCacheActiveLockWhenAcdActive();
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
     * Disable APO whenever ACD is active.
     */
    private function disableApoWhenAcdActive(): void
    {
        if (self::isActive()) {
            setOptionOverride('use_cloudflare_apo', '__return_false');
        }
    }

    /**
     * Activate automatic cache purge feature on ACD activation.
     */
    private function activateCachePurgeFeatureOnAcdActivation(): void
    {
        add_action('sb_optimizer_acd_enable', function() {
            CachePurge::setActiveState(true);
        });
    }

    /**
     * Lock cache purge driver to ACD whenever ACD-feature is active.
     */
    private function cachePurgeDriverLockWhenAcdActive(): void
    {
        if (self::isActive()) {
            $acdFunction = function() {
                return 'acd';
            };
            setOptionOverride('cache_purge_driver', $acdFunction);
            add_filter('sb_optimizer_selected_cache_purge_driver', $acdFunction);
        }
    }

    /**
     * Lock html cache as active whenever ACD-feature is active.
     */
    private function htmlCacheActiveLockWhenAcdActive(): void
    {
        if (self::isActive()) {
            setOptionOverride('fpc_switch', '__return_true');
            add_filter('sb_optimizer_fpc_is_active', '__return_true');
        }
    }
}
