<?php

namespace Servebolt\Optimizer\CachePurge\Drivers;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Api\Cloudflare\Cloudflare as CloudflareApi;
use Servebolt\Optimizer\CachePurge\Interfaces\CachePurgeUrlInterface;
use Servebolt\Optimizer\CachePurge\Interfaces\CachePurgeAllInterface;
use Servebolt\Optimizer\CachePurge\Interfaces\CachePurgeValidateUrlCandidate;
use Servebolt\Optimizer\Exceptions\CloudflareApiError;
use Servebolt\Optimizer\Traits\Singleton;
use Servebolt\Optimizer\Sdk\Cloudflare\Exceptions\ApiError as CloudflareSdkApiError;

/**
 * Class Cloudflare
 * @package Servebolt\Optimizer\CachePurge\Drivers
 */
class Cloudflare implements CachePurgeAllInterface, CachePurgeUrlInterface, CachePurgeValidateUrlCandidate
{
    use Singleton;

    /**
     * Weed out URL's that can never be cached.
     * @param string $url
     * @return bool
     */
    public function validateUrl(string $url): bool
    {
        $path = parse_url($url, PHP_URL_PATH);
        if (empty($path)) {
            return false;
        }
        $never_cached_paths = [
            '/wp-admin/',
            '/index.php/',
        ];
        foreach ($never_cached_paths as $never_cached_path) {
            if (strpos($path, $never_cached_path) !== false) {
                return false;
            }
        }
        return true;
    }

    /**
     * Purge a URL.
     *
     * @param string $url
     * @return bool
     * @throws CloudflareApiError
     */
    public function purgeByUrl(string $url): bool
    {
        try {
            $instance = CloudflareApi::getInstance();
            if ($instance->isConfigured()) {
                $instance->purgeUrl($url);
                return true;
            }
            return false;
        } catch (CloudflareSdkApiError $e) {
            throw new CloudflareApiError(
                $e->getErrors(),
                $e->getResponse()
            );
        }
    }

    /**
     * Purge an array of URLs.
     *
     * @param array $urls
     * @return bool
     * @throws CloudflareApiError
     */
    public function purgeByUrls(array $urls): bool
    {
        try {
            $instance = CloudflareApi::getInstance();
            if ($instance->isConfigured()) {
                $instance->purgeUrls($urls);
                return true;
            }
            return false;
        } catch (CloudflareSdkApiError $e) {
            throw new CloudflareApiError(
                $e->getErrors(),
                $e->getResponse()
            );
        }
    }

    /**
     * Purge all URL's (in the current zone).
     * @return bool
     * @throws CloudflareApiError
     */
    public function purgeAll(): bool
    {
        try {
            $instance = CloudflareApi::getInstance();
            if ($instance->isConfigured()) {
                $instance->purgeAll();
                return true;
            }
            return false;
        } catch (CloudflareSdkApiError $e) {
            throw new CloudflareApiError(
                $e->getErrors(),
                $e->getResponse()
            );
        }
    }

    /**
     * Purge an array of tags.
     *
     * @param array $urls
     * @return bool
     * @throws CloudflareApiError
     */
    public function purgeByTags(array $tags): bool
    {
        try {
            $instance = CloudflareApi::getInstance();
            if ($instance->isConfigured()) {
                $instance->purgeTags($tags);
                return true;
            }
            return false;
        } catch (CloudflareSdkApiError $e) {
            throw new CloudflareApiError(
                $e->getErrors(),
                $e->getResponse()
            );
        }
    }
}
