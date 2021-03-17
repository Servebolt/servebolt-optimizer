<?php

namespace Servebolt\Optimizer\CachePurge\Drivers;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

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
            $instance->purgeUrl($url);
            return true;
        } catch (CloudflareSdkApiError $e) {
            $response = $e->getResponse();
            throw new CloudflareApiError(
                $response->getErrors(),
                $response
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
            $instance->purgeUrls($urls);
            return true;
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
            $instance->purgeAll();
            return true;
        } catch (CloudflareSdkApiError $e) {
            throw new CloudflareApiError(
                $e->getErrors(),
                $e->getResponse()
            );
        }
    }
}
