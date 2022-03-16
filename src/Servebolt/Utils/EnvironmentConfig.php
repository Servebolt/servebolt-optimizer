<?php

namespace Servebolt\Optimizer\Utils;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Traits\Singleton;
use function Servebolt\Optimizer\Helpers\isHostedAtServebolt;
use function Servebolt\Optimizer\Helpers\isValidJson;

/**
 * Class EnvironmentConfig
 * @package Servebolt\Optimizer\Utils
 */
class EnvironmentConfig
{
    use Singleton;

    /**
     * @var string The suffix to append to the site URL.
     */
    private $suffix = 'acd-cgi/config';

    /**
     * @var int Timeout in seconds when retrieving environment config.
     */
    private $requestTimeout = 2;

    /**
     * @var string The key used for cache storage.
     */
    private $cacheKey = 'sb_optimizer_environment_config';

    /**
     * @var bool Whether to cache the environment config.
     */
    private $cacheActive = false;

    /**
     * @var float|int The Cache TTL.
     */
    private $cacheTtl = MONTH_IN_SECONDS;

    /**
     * @var null Storage of environment config.
     */
    private $configObject = null;

    /**
     * Get config item from environment config data.
     *
     * @param string $key
     * @param bool $purgeCache
     * @return mixed|null
     */
    public function get(string $key, bool $purgeCache = false)
    {
        if ($purgeCache) {
            $this->reset();
        }
        if ($this->retrieveConfig()) {
            return $this->getFromConfig($key);
        }
        return null;
    }

    /**
     * Reset / purge cache.
     */
    public function reset(): void
    {
        delete_transient($this->cacheKey);
        $this->configObject = null;
    }

    /**
     * Get config item from environment config data.
     *
     * @param string $key
     * @return mixed|null
     */
    private function getFromConfig(string $key)
    {
        if (
            $this->configObject
            && isset($this->configObject->{$key})
        ) {
            /**
             * Filter value before returning.
             *
             * @param mixed $value The value of the obtained environment config item.
             * @param string $key The key of the obtained environment config item.
             */
            return apply_filters(
                'sb_optimizer_environment_config_item',
                $this->configObject->{$key},
                $key
            );
        }
        return null;
    }

    /**
     * Retrieve config and store it in class.
     *
     * @return bool
     */
    public function retrieveConfig(): bool
    {
        if (!isHostedAtServebolt()) {
            return false;
        }
        if (is_null($this->configObject)) {
            if ($this->cacheActive && $cachedConfigObject = $this->retrieveCachedConfigObject()) {
                $this->setConfigObject($cachedConfigObject);
            } elseif ($configObject = $this->requestConfigObject()) {
                if ($this->cacheActive) {
                    $this->setCachedConfigObject($configObject);
                }
                $this->setConfigObject($configObject);
            }
        }
        return !is_null($this->configObject);
    }

    /**
     * Get config object from cache.
     *
     * @return false|mixed
     */
    private function retrieveCachedConfigObject()
    {
        if ($cachedConfigObject = get_transient($this->cacheKey)) {
            return $cachedConfigObject;
        }
        return false;
    }

    /**
     * Set config object cache.
     *
     * @param $configObject
     */
    private function setCachedConfigObject($configObject): void
    {
        set_transient($this->cacheKey, $configObject, $this->cacheTtl);
    }

    /**
     * Set config object.
     *
     * @param $configObject
     */
    public function setConfigObject($configObject)
    {
        $this->configObject = $configObject;
    }

    /**
     * Request config object.
     *
     * @return false|mixed
     */
    public function requestConfigObject()
    {
        $response = wp_remote_get($this->getEnvironmentConfigUrl(), [
            'timeout' => $this->requestTimeout,
            'sslverify' => false,
        ]);
        $httpCode = wp_remote_retrieve_response_code($response);
        if ($httpCode !== 200) {
            return false;
        }
        $body = wp_remote_retrieve_body($response);
        if (!isValidJson($body)) {
            return false;
        }
        if ($json = json_decode($body)) {
            return $json;
        }
        return false;
    }

    /**
     * Get URL to environment check endpoint.
     *
     * @return string
     */
    public function getEnvironmentConfigUrl(): string
    {
        return $this->getSiteUrl() . $this->suffix;
    }

    /**
     * Get site URL.
     *
     * @return string
     */
    public function getSiteUrl(): string
    {
        /**
         * Filter site URL to be used when obtaining the environment config.
         *
         * @param mixed $siteUrl The value of the obtained environment config item.
         */
        return trailingslashit(apply_filters(
            'sb_optimizer_environment_config_base_url',
            get_site_url()
        ));
    }
}
