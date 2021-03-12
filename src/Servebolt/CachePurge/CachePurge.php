<?php

namespace Servebolt\Optimizer\CachePurge;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use Servebolt\Optimizer\Traits\Singleton;
use Servebolt\Optimizer\CachePurge\Drivers\Servebolt as ServeboltDriver;
use Servebolt\Optimizer\CachePurge\Drivers\Cloudflare as CloudflareDriver;

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
     * @param null|int $blogId
     * @throws \ReflectionException
     */
    private function __construct($blogId = null)
    {
        $this->driver = $this->resolveDriver($blogId);
    }

    /**
     * @return mixed
     */
    public function getDriverObject()
    {
        return $this->driver;
    }

    /**
     * @param null|int $blogId
     * @return mixed
     * @throws \ReflectionException
     */
    private function resolveDriver($blogId = null)
    {
        if (
            $this->cachePurgeIsActive($blogId)
            && $this->cloudflareIsSelected($blogId)
            && $this->cloudflareIsConfigured($blogId)
        ) {
            return CloudflareDriver::getInstance();
        }
        if (
            $this->isHostedAtServebolt($blogId)
            && $this->cachePurgeIsActive($blogId)
            && $this->acdIsSelected($blogId)
            && $this->acdIsConfigured($blogId)
        ) {
            return ServeboltDriver::getInstance();
        }
        // Handle when no driver is given?
    }

    /**
     * @param null $blogId
     * @return bool
     */
    public function cachePurgeIsActive($blogId = null): bool
    {
        $key = 'cache_purge_active';
        if (is_numeric($blogId)) {
            return sb_checkbox_true(sb_get_blog_option($blogId, $key));
        } else {
            return sb_checkbox_true(sb_get_option($key));
        }
    }

    private function cloudflareIsSelected($blogId = null): bool
    {
        return $this->getSelectedCachePurgeDriver($blogId) == 'cloudflare';
    }

    private function getSelectedCachePurgeDriver($blogId)
    {
        $key = 'cache_purge_selector';
        if (is_numeric($blogId)) {
            return sb_get_blog_option($blogId, $key);
        } else {
            return sb_get_option($key);
        }
    }

    private function cloudflareIsConfigured($blogId = null) : bool
    {
        // TODO: Add checks to see if Cloudflare is configured
    }

    private function isHostedAtServebolt($blogId = null) : bool
    {
        // TODO: Make sure that site is hosted at servebolt
    }

    private function acdIsSelected($blogId = null) : bool
    {
        return $this->getSelectedCachePurgeDriver($blogId) == 'acd';
    }

    private function acdIsConfigured($blogId = null) : bool
    {
        // TODO: Add checks to see if Cloudflare is configured
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

}
