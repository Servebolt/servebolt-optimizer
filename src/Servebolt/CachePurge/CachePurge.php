<?php

namespace Servebolt\Optimizer\CachePurge;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Traits\Singleton;
use Servebolt\Optimizer\Api\Servebolt\Servebolt as ServeboltApi;
use Servebolt\Optimizer\Api\Cloudflare\Cloudflare as CloudflareApi;
use Servebolt\Optimizer\CachePurge\Drivers\Servebolt as ServeboltDriver;
use Servebolt\Optimizer\CachePurge\Drivers\Cloudflare as CloudflareDriver;
use function Servebolt\Optimizer\Helpers\checkboxIsChecked;
use function Servebolt\Optimizer\Helpers\isHostedAtServebolt;
use function Servebolt\Optimizer\Helpers\getBlogOption;
use function Servebolt\Optimizer\Helpers\getOption;

/**
 * Class CachePurge
 *
 * This class will resolve the cache purge driver, and forward the cache purge request to it.
 *
 * @package Servebolt\Optimizer\CachePurge
 */
class CachePurge
{

    use Singleton;

    /**
     * Cache purge driver instance.
     *
     * @var mixed
     */
    private $driver;

    /**
     * CachePurge constructor.
     * @param int|null $blogId
     */
    private function __construct(?int $blogId = null)
    {
        $this->driver = $this->resolveDriverObject($blogId);
    }

    /**
     * Proxy call to cache purge driver.
     *
     * @param $name
     * @param $arguments
     * @return false|mixed
     */
    public function __call($name, $arguments)
    {
        if (is_object($this->driver) && is_callable([$this->driver, $name])) {
            return call_user_func_array([$this->driver, $name], $arguments);
        } else {
            trigger_error(sprintf('Call to undefined method %s', $name));
        }
    }

    /**
     * @return mixed
     */
    public function getDriverObject()
    {
        return $this->driver;
    }

    /**
     * @param int|null $blogId
     * @param bool $verbose
     * @return string
     */
    public static function resolveDriver(?int $blogId = null, bool $verbose = false): ?string
    {
        if (
            self::isActive($blogId)
            && self::cloudflareIsSelected($blogId)
            && self::cloudflareIsConfigured($blogId)
        ) {
            return $verbose ? 'Cloudflare' : 'cloudflare';
        }
        if (
            isHostedAtServebolt()
            && self::isActive($blogId)
            && self::acdIsSelected($blogId)
            && self::acdIsConfigured()
        ) {
            return $verbose ? 'Accelerated domains' : 'acd';
        }
        return null;
    }

    /**
     * @param null|int $blogId
     * @return mixed
     */
    private function resolveDriverObject(?int $blogId = null)
    {
        $driver = $this->resolveDriver($blogId);
        if ($driver === 'cloudflare') {
            return CloudflareDriver::getInstance();
        }
        if ($driver === 'acd') {
            return ServeboltDriver::getInstance();
        }
        // Handle when no driver is given?
    }

    /**
     * Check whether the cache purge feature is activated and configured (available for use).
     *
     * @param int|null $blogId
     * @return bool
     */
    public static function featureIsAvailable(?int $blogId = null): bool
    {
        return self::isActive($blogId) && self::featureIsConfigured($blogId);
    }

    /**
     * Alias for "featureIsAvailable".
     *
     * @param int|null $blogId
     * @return bool
     */
    public static function featureIsActive(?int $blogId = null): bool
    {
        return self::featureIsAvailable($blogId);
    }

    /**
     * Check whether cache purge feature is configured correctly and ready to use.
     *
     * @param int|null $blogId
     * @return bool
     */
    public static function featureIsConfigured(?int $blogId = null): bool
    {
        if (
            self::cloudflareIsSelected($blogId)
            && self::cloudflareIsConfigured($blogId)
        ) {
            return true;
        }
        if (
            self::acdIsSelected($blogId)
            && self::acdIsConfigured($blogId)
        ) {
            return true;
        }
        return false;
    }

    /**
     * Whether we should purge cache automatically when content is updated.
     *
     * @param int|null $blogId
     * @return bool
     */
    public static function automaticCachePurgeOnContentUpdateIsActive(?int $blogId = null): bool
    {
        $noExistKey = 'value-does-not-exist';
        $key = 'cache_purge_auto';
        if (is_numeric($blogId)) {
            $value = getBlogOption($blogId, $key, $noExistKey);
        } else {
            $value = getOption($key, $noExistKey);
        }
        if ($value === $noExistKey) {
            return true; // Default value
        }
        return checkboxIsChecked($value);
    }

    /**
     * Check whether the cache purge feature is active.
     *
     * @param int|null $blogId
     * @return bool
     */
    public static function isActive(?int $blogId = null): bool
    {
        $noExistKey = 'value-does-not-exist';
        $key = 'cache_purge_switch';
        if (is_numeric($blogId)) {
            $value = getBlogOption($blogId, $key, $noExistKey);
        } else {
            $value = getOption($key, $noExistKey);
        }
        if ($value === $noExistKey) {
            $key = 'cf_switch';
            if (is_numeric($blogId)) {
                $value = getBlogOption($blogId, $key);
            } else {
                $value = getOption($key);
            }
        }
        return checkboxIsChecked($value);
    }

    /**
     * Check whether Cloudflare is selected as cache purge driver.
     *
     * @param int|null $blogId
     * @return bool
     */
    private static function cloudflareIsSelected(?int $blogId = null): bool
    {
        return self::getSelectedCachePurgeDriver($blogId) == 'cloudflare';
    }

    /**
     * Get the selected cache purge driver.
     *
     * @param int|null $blogId
     * @return mixed|void
     */
    private static function getSelectedCachePurgeDriver(?int $blogId = null)
    {
        $key = 'cache_purge_driver';
        if (is_numeric($blogId)) {
            return getBlogOption($blogId, $key);
        } else {
            return getOption($key);
        }
    }

    /**
     * Check whether ACD is selected as cache purge driver.
     *
     * @param int|null $blogId
     * @return bool
     */
    private static function acdIsSelected(?int $blogId = null): bool
    {
        return self::getSelectedCachePurgeDriver($blogId) == 'acd';
    }

    /**
     * @param int|null $blogId
     * @return bool
     */
    private static function cloudflareIsConfigured(?int $blogId = null): bool
    {
        if (is_int($blogId)) {
            $cfApi = new CloudflareApi($blogId);
        } else {
            $cfApi = CloudflareApi::getInstance();
        }
        return $cfApi->isConfigured();
    }

    /**
     * @return bool
     */
    private static function acdIsConfigured(): bool
    {
        $sbApi = ServeboltApi::getInstance();
        return $sbApi->isConfigured();
    }

    /**
     * Check whether we have set a boolean override value for the cron.
     *
     * @return bool
     */
    public static function queueBasedCachePurgeActiveStateIsOverridden(): bool
    {
        return
            (defined('SERVEBOLT_CF_PURGE_CRON') && is_bool(SERVEBOLT_CF_PURGE_CRON)) // Legacy
            || ( defined('SERVEBOLT_QUEUE_BASED_CACHE_PURGE') && is_bool(SERVEBOLT_QUEUE_BASED_CACHE_PURGE) );

    }

    /**
     * Check if we have overridden whether the Cron purge should be active or not.
     *
     * @return mixed
     */
    public static function queueBasedCachePurgeActiveStateOverride(): ?bool
    {
        if ( self::queueBasedCachePurgeActiveStateIsOverridden() ) {
            return SERVEBOLT_CF_PURGE_CRON;
        }
        return null;
    }

    /**
     * Check whether the Cron-based cache purger should be active.
     *
     * @param bool $respectOverride
     * @param int|null $blogId
     *
     * @return bool
     */
    public static function queueBasedCachePurgeIsActive(bool $respectOverride = true, ?int $blogId = null): bool
    {
        $activeStateOverride = self::queueBasedCachePurgeActiveStateOverride();
        if ( $respectOverride && is_bool($activeStateOverride) ) {
            return $activeStateOverride;
        }

        $noExistKey = 'value-does-not-exist';
        $key = 'queue_based_cache_purge';
        if (is_numeric($blogId)) {
            $value = getBlogOption($blogId, $key, $noExistKey);
        } else {
            $value = getOption($key, $noExistKey);
        }
        if ($value === $noExistKey) {
            $key = 'cf_cron_purge';
            if (is_numeric($blogId)) {
                $value = getBlogOption($blogId, $key);
            } else {
                $value = getOption($key);
            }
        }
        return checkboxIsChecked($value);
    }
}
