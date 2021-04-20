<?php

namespace Servebolt\Optimizer\FullPageCache;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Traits\Singleton;
use function Servebolt\Optimizer\Helpers\checkboxIsChecked;
use function Servebolt\Optimizer\Helpers\smartGetOption;

/**
 * Class FullPageCache
 * @package Servebolt\Optimizer\FullPageCache
 */
class FullPageCache
{
    use Singleton;

    /**
     * Whether to use the Cloudflare APO-feature.
     *
     * @var bool
     */
    private $cfApoActive = null;

    /**
     * FullPageCache constructor.
     */
    public function __construct()
    {
        FullPageCacheHeaders::init();
    }

    /**
     * Check if full page caching is active with optional blog check.
     *
     * @param null|int $blogId
     *
     * @return bool
     */
    public static function fpcIsActive(?int $blogId = null): bool
    {
        return (bool) apply_filters('sb_optimizer_fpc_is_active', checkboxIsChecked(smartGetOption($blogId, self::fpcActiveOptionKey())));
    }

    /**
     * Check whether we have overridden the active status for full page cache.
     *
     * @return bool
     */
    public static function fpcActiveStateIsOverridden(): bool
    {
        return has_filter('sb_optimizer_fpc_is_active');
    }
}
