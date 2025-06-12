<?php

namespace Servebolt\Optimizer\CachePurge;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Traits\Multiton;
use Servebolt\Optimizer\Api\Servebolt\Servebolt as ServeboltApi;
use Servebolt\Optimizer\Api\Cloudflare\Cloudflare as CloudflareApi;
use Servebolt\Optimizer\CachePurge\Drivers\Servebolt as ServeboltDriver;
use Servebolt\Optimizer\CachePurge\Drivers\ServeboltCdn as ServeboltCdnDriver;
use Servebolt\Optimizer\CachePurge\Drivers\Cloudflare as CloudflareDriver;
use function Servebolt\Optimizer\Helpers\checkboxIsChecked;
use function Servebolt\Optimizer\Helpers\isHostedAtServebolt;
use function Servebolt\Optimizer\Helpers\smartGetOption;
use function Servebolt\Optimizer\Helpers\smartUpdateOption;
use function Servebolt\Optimizer\Helpers\isNextGen;

/**
 * Class CachePurge
 *
 * This class will resolve the cache purge driver, and forward the cache purge request to it.
 *
 * @package Servebolt\Optimizer\CachePurge
 */
class CachePurge
{

    use Multiton;

    /**
     * Cache purge driver instance.
     *
     * @var mixed
     */
    private $driver;

    /**
     * Drivers that require the site to be hosted at Servebolt.
     *
     * @var string[]
     */
    private static $serveboltOnlyDrivers = ['acd', 'serveboltcdn'];

    /**
     * Valid drivers.
     *
     * @var string[]
     */
    private static $validDrivers = ['cloudflare', 'acd', 'serveboltcdn'];

    /**
     * CachePurge constructor.
     * @param int|null $blogId
     */
    public function __construct(?int $blogId = null)
    {
        $this->driver = self::resolveDriverObject($blogId);
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
     * Get the name of the selected driver without checking for proper configuration.
     *
     * @param int|null $blogId
     * @param bool $verbose
     * @return string
     */
    public static function resolveDriverNameWithoutConfigCheck(?int $blogId = null, bool $verbose = false): string
    {
        $isActive = self::isActive($blogId);
        if (
            $isActive
            && self::cloudflareIsSelected($blogId)
        ) {
            return $verbose ? 'Cloudflare' : 'cloudflare';
        }
        if (
            isHostedAtServebolt()
            && $isActive
        ) {
            if (self::serveboltCdnIsSelected($blogId)) {
                return $verbose ? 'Servebolt CDN' : 'serveboltcdn';
            }
            if (self::acdIsSelected($blogId)) {
                return $verbose ? 'Accelerated Domains' : 'acd';
            }
        }
        return self::defaultDriverName($verbose);
    }

    /**
     * Get the selected driver name.
     *
     * @param int|null $blogId
     * @param bool $verbose
     * @return string
     */
    public static function resolveDriverName(?int $blogId = null, bool $verbose = false): string
    {
        $isActive = self::isActive($blogId);
        if (
            $isActive
            && self::cloudflareIsSelected($blogId)
            && self::cloudflareIsConfigured($blogId)
        ) {
            return $verbose ? 'Cloudflare' : 'cloudflare';
        }
        if (
            isHostedAtServebolt()
            && $isActive
        ) {
            if (
                self::serveboltCdnIsSelected($blogId)
                && self::serveboltCdnIsConfigured()
            ) {
                return $verbose ? 'Servebolt CDN' : 'serveboltcdn';
            }
            if (
                self::acdIsSelected($blogId)
                && self::acdIsConfigured()
            ) {
                return $verbose ? 'Accelerated Domains' : 'acd';
            }
        }
        return self::defaultDriverName($verbose);
    }

    /**
     * Resolve driver object.
     *
     * @param null|int $blogId
     * @return mixed
     */
    private static function resolveDriverObject(?int $blogId = null)
    {
        $driver = self::resolveDriverName($blogId);
        if ($driver === 'serveboltcdn') {
            return ServeboltCdnDriver::getInstance();
        } elseif ($driver === 'acd') {
            return ServeboltDriver::getInstance();
        } elseif ($driver === 'cloudflare') {
            return CloudflareDriver::getInstance();
        }
        return self::defaultDriverObject();
    }

    /**
     * Get default driver name.
     *
     * @param bool $verbose
     * @return string
     */
    private static function defaultDriverName(bool $verbose = false): string
    {
        return $verbose ? 'Cloudflare' : 'cloudflare';
    }

    /**
     * Get default driver object.
     *
     * @return mixed
     */
    private static function defaultDriverObject()
    {
        return CloudflareDriver::getInstance();
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
     * Check whether cache purge by URL is available.
     *
     * @param int|null $blogId
     * @return bool
     */
    public static function cachePurgeByUrlIsAvailable(?int $blogId = null) : bool
    {
        if(self::serveboltCdnIsSelected($blogId)) return false;
        return true;
    }

    /**
     * Check whether cache purge by server is available.
     *
     * @param int|null $blogId
     * @return bool
     */
    public static function cachePurgeByServerAvailable(?int $blogId = null) : bool
    {
        if(!isNextGen()) return false;
        if(self::serveboltCdnIsSelected($blogId)) return true;
        if(self::acdIsSelected($blogId)) return true;
        return false;
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
        if (
            self::serveboltCdnIsSelected($blogId)
            && self::serveboltCdnIsConfigured($blogId)
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
        return checkboxIsChecked(
            smartGetOption($blogId, 'cache_purge_auto')
        );
    }

    /**
     * Whether we should use Cache Tags for Cloudflare
     *
     * @param int|null $blogId
     * @return bool
     */
    public static function cfCacheTagsIsActive(?int $blogId = null): bool
    {
        return checkboxIsChecked(
            smartGetOption($blogId, 'cf_cache_tags')
        );
    }

    /**
     * Whether we should purge cache automatically when an attachment is updated.
     *
     * @param int|null $blogId
     * @return bool
     */
    public static function automaticCachePurgeOnAttachmentUpdateIsActive(?int $blogId = null): bool
    {
        return checkboxIsChecked(
            smartGetOption($blogId, 'cache_purge_auto_on_attachment_update')
        );
    }

    /**
     * Whether we should purge cache automatically when a post/term is deleted.
     *
     * @param int|null $blogId
     * @return bool
     */
    public static function automaticCachePurgeOnDeletionIsActive(?int $blogId = null): bool
    {
        return checkboxIsChecked(
            smartGetOption($blogId, 'cache_purge_auto_on_deletion')
        );
    }

    /**
     * Whether we should purge cache automatically when the slug is updated.
     *
     * @param int|null $blogId
     * @return bool
     */
    public static function automaticCachePurgeOnSlugChangeIsActive(?int $blogId = null): bool
    {
        return checkboxIsChecked(
            smartGetOption($blogId, 'cache_purge_auto_on_slug_change')
        );
    }

    /**
     * Set cache purge feature state.
     *
     * @param bool $boolean
     * @param int|null $blogId
     * @return bool
     */
    public static function setActiveState($boolean, ?int $blogId = null): bool
    {
        return smartUpdateOption($blogId, 'cache_purge_switch', $boolean, true);
    }

    /**
     * Check whether the cache purge feature is active.
     *
     * @param int|null $blogId
     * @return bool
     */
    public static function isActive(?int $blogId = null): bool
    {
        return apply_filters(
            'sb_optimizer_cache_purge_feature_active',
            checkboxIsChecked(
                smartGetOption(
                    $blogId,
                    'cache_purge_switch',
                    smartGetOption($blogId, 'cf_switch')
                )
            )
        );
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
     * @param bool $strict
     * @return string
     */
    public static function getSelectedCachePurgeDriver(?int $blogId = null, bool $strict = true)
    {
        $defaultDriver = self::defaultDriverName();
        $driver = (string) apply_filters(
            'sb_optimizer_selected_cache_purge_driver',
            smartGetOption(
                $blogId,
                'cache_purge_driver',
                $defaultDriver
            )
        );
        if (!in_array($driver, self::$validDrivers)) {
            $driver = $defaultDriver;
        } else if ($strict && !isHostedAtServebolt() && in_array($driver, self::$serveboltOnlyDrivers)) {
            $driver = $defaultDriver;
        }
        return $driver;
    }

    /**
     * Check whether cache purge driver is locked to given driver.
     *
     * @param $driver
     * @return bool
     */
    public static function cachePurgeIsLockedTo($driver)
    {
        return self::cachePurgeDriverIsOverridden() && self::getSelectedCachePurgeDriver() === $driver;
    }

    /**
     * Check whether cache purge driver is overridden using a filter.
     *
     * @return bool
     */
    public static function cachePurgeDriverIsOverridden(): bool
    {
        return has_filter('sb_optimizer_selected_cache_purge_driver');
    }

    /**
     * Check whether Servebolt CDN is selected as cache purge driver.
     *
     * @param int|null $blogId
     * @return bool
     */
    private static function serveboltCdnIsSelected(?int $blogId = null): bool
    {
        return self::getSelectedCachePurgeDriver($blogId) == 'serveboltcdn';
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
     * Check whether the API-instance for Accelerated Domains is configured.
     *
     * @return bool
     */
    private static function acdIsConfigured(): bool
    {
        if (apply_filters('sb_optimizer_acd_is_configured', false)) {
            return true;
        }
        $sbApi = ServeboltApi::getInstance();
        return $sbApi->isConfigured();
    }

    /**
     * Alias for "driverSupportsUrlCachePurge" to check whether we can purge cache for WP items (through URL cache purging).
     *
     * @return bool
     */
    public static function driverSupportsItemCachePurge(): bool
    {
        return self::driverSupportsUrlCachePurge();
    }

    /**
     * Check if the current driver requires Servebolt hosting.
     *
     * @return bool
     */
    public static function driverRequiresServeboltHosting(): bool
    {
        return in_array(self::resolveDriverNameWithoutConfigCheck(), self::$serveboltOnlyDrivers);
    }

    /**
     * Check if the current driver supports CacheTag purging.
     *
     * @return bool
     */
    public static function driverSupportsCacheTagPurge(): bool
    {
        $driver = self::resolveDriverObject();
        $interfaces = class_implements($driver);
        return is_array($interfaces)
            && in_array('Servebolt\Optimizer\CachePurge\Interfaces\CachePurgeTagInterface', $interfaces);
    }

    /**
     * Check if the current driver supports URL cache purging.
     *
     * @return bool
     */
    public static function driverSupportsUrlCachePurge(): bool
    {
        $driver = self::resolveDriverObject();
        $interfaces = class_implements($driver);
        return is_array($interfaces)
            && in_array('Servebolt\Optimizer\CachePurge\Interfaces\CachePurgeUrlInterface', $interfaces);
    }
    /**
     * Check if the current driver supports prefix cache purging.
     *
     * @return bool
     */
    public static function driverSupportsUrlCacheTagPurge(): bool
    {
        $driver = self::resolveDriverObject();
        $interfaces = class_implements($driver);
        return is_array($interfaces)
            && in_array('Servebolt\Optimizer\CachePurge\Interfaces\CachePurgeTagInterface', $interfaces);
    }
    
    /**
     * Check if the current driver supports prefix cache purging.
     *
     * @return bool
     */
    public static function driverSupportsUrlCachePrefixPurge(): bool
    {
        $driver = self::resolveDriverObject();
        $interfaces = class_implements($driver);
        return is_array($interfaces)
            && in_array('Servebolt\Optimizer\CachePurge\Interfaces\CachePurgePrefixInterface', $interfaces);
    }

    /**
     * Check if the current driver supports cache all purging.
     *
     * @return bool
     */
    public static function driverSupportsCachePurgeAll(): bool
    {
        $driver = self::resolveDriverObject();
        $interfaces = class_implements($driver);
        return is_array($interfaces)
            && in_array('Servebolt\Optimizer\CachePurge\Interfaces\CachePurgeAllInterface', $interfaces);
    }

    /**
     * Check if the current driver supports cache all purging.
     *
     * @return bool
     */
    public static function driverSupportsCachePurgeServer(): bool
    {
        $driver = self::resolveDriverObject();
        $interfaces = class_implements($driver);
        return is_array($interfaces)
            && in_array('Servebolt\Optimizer\CachePurge\Interfaces\CachePurgeServerInterface', $interfaces);
    }

    /**
     * Check if automatic cache purging is available.
     *
     * @return bool
     */
    public static function automaticCachePurgeIsAvailable(): bool
    {
        if (apply_filters('sb_optimizer_automatic_cache_purge_is_available', false)) {
            return true;
        }
        return self::driverSupportsUrlCachePurge();
    }

    /**
     * Check whether the API-instance for Servebolt CDN is configured.
     *
     * @return bool
     */
    private static function serveboltCdnIsConfigured(): bool
    {
        if (apply_filters('sb_optimizer_serveboltcdn_is_configured', false)) {
            return true;
        }
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
            || (defined('SERVEBOLT_QUEUE_BASED_CACHE_PURGE') && is_bool(SERVEBOLT_QUEUE_BASED_CACHE_PURGE));

    }

    /**
     * Check if we have overridden whether the Cron purge should be active or not.
     *
     * @return mixed
     */
    public static function queueBasedCachePurgeActiveStateOverride(): ?bool
    {
        if (self::queueBasedCachePurgeActiveStateIsOverridden()) {
            if (defined('SERVEBOLT_CF_PURGE_CRON')) {
                return SERVEBOLT_CF_PURGE_CRON; // Legacy
            } else {
                return SERVEBOLT_QUEUE_BASED_CACHE_PURGE;
            }
        }
        return null;
    }

    /**
     * Check whether the Cron-based cache purger should be active.
     *
     * @param int|null $blogId
     * @param bool $respectOverride
     *
     * @return bool
     */
    public static function queueBasedCachePurgeIsActive(?int $blogId = null, bool $respectOverride = true): bool
    {
        $activeStateOverride = self::queueBasedCachePurgeActiveStateOverride();
        if ($respectOverride && is_bool($activeStateOverride)) {
            return $activeStateOverride;
        }
        return checkboxIsChecked(
            smartGetOption(
                $blogId,
                'queue_based_cache_purge',
                smartGetOption($blogId, 'cf_cron_purge')
            )
        );
    }
}
