<?php

namespace Servebolt\Optimizer\CachePurge\Drivers;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Api\Cloudflare\Cloudflare as CloudflareApi;
use Servebolt\Optimizer\Exceptions\CloudflareApiError;
use Servebolt\Optimizer\Traits\Singleton;
use Servebolt\Optimizer\CachePurge\Interfaces\CachePurgeInterface;
use Servebolt\Optimizer\Sdk\Cloudflare\Exceptions\ApiError as CloudflareSdkApiError;

class Cloudflare implements CachePurgeInterface
{
    use Singleton;

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
}
