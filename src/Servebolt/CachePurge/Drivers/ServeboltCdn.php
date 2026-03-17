<?php

namespace Servebolt\Optimizer\CachePurge\Drivers;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\CachePurge\Interfaces\CachePurgeAllInterface;
use Servebolt\Optimizer\CachePurge\Interfaces\CachePurgeUrlInterface;
use Servebolt\Optimizer\CachePurge\Interfaces\CachePurgeTagInterface;
use Servebolt\Optimizer\CachePurge\Interfaces\CachePurgeValidateUrlCandidate;
use Servebolt\Optimizer\CachePurge\Interfaces\CachePurgeServerInterface;
use Servebolt\Optimizer\CachePurge\Interfaces\CachePurgeOpCacheInterface;
use Servebolt\Optimizer\Traits\Singleton;
use Servebolt\Optimizer\Exceptions\ServeboltApiError;
use function Servebolt\Optimizer\Helpers\getDomainNameOfWebSite;

/**
 * Class ServeboltCdn
 * @package Servebolt\Optimizer\CachePurge\Drivers
 */
class ServeboltCdn implements CachePurgeAllInterface, CachePurgeTagInterface, CachePurgeUrlInterface, CachePurgeValidateUrlCandidate, CachePurgeServerInterface, CachePurgeOpCacheInterface
{
    use Singleton, ServeboltDriverTrait;

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
        foreach($never_cached_paths as $never_cached_path) {
            if (strpos($path, $never_cached_path) !== false) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param string $url
     * @return bool
     * @throws ServeboltApiError
     */
    public function purgeByUrl(string $url): bool
    {
        $response = $this->apiInstance->environment()->purgeCdnCache(
            $this->apiInstance->getEnvironmentId(),
            [$url] // files
            // prefixes
            // tags
            // hosts
        );
        $this->debugRequest('serveboltcdn', __FUNCTION__, [
            'files' => [$url],
        ], $response);
        if ($response->wasSuccessful()) {
            return true;
        } else {
            throw new ServeboltApiError($response->getErrors(), $response, 'serveboltcdn');
        }
    }

    /**
     * @param array $urls
     * @return bool
     * @throws ServeboltApiError
     */
    public function purgeByUrls(array $urls): bool
    {
        $response = $this->apiInstance->environment->purgeCdnCache(
            $this->apiInstance->getEnvironmentId(),
            $urls// files
            // prefixes
            // tags
            // hosts
        );
        $this->debugRequest('serveboltcdn', __FUNCTION__, [
            'files' => $urls,
        ], $response);
        if ($response->wasSuccessful()) {
            return true;
        } else {
            throw new ServeboltApiError($response->getErrors(), $response, 'serveboltcdn');
        }
    }

    /**
     * Purge CDN cache (for a single site).
     *
     * @return bool
     * @throws ServeboltApiError
     */
    public function purgeAll(): bool
    {
        $prefixes = $this->getPurgeAllPrefixes();
        $response = $this->apiInstance->environment->purgeCdnCache(
            $this->apiInstance->getEnvironmentId(),
            [], // files
            $prefixes // prefixes
            // tags
            // hosts
        );
        $this->debugRequest('serveboltcdn', __FUNCTION__, [
            'files' => [],
            'prefixes' => $prefixes,
        ], $response);
        if ($response->wasSuccessful()) {
            return true;
        } else {
            throw new ServeboltApiError($response->getErrors(), $response, 'serveboltcdn');
        }
    }

    /**
     * Purge all cache (for a single site).
     *
     * @return bool
     * @throws ServeboltApiError
     */
    public function purgeServer(): bool
    {
        $response = $this->apiInstance->environment->purgeServerCache(
            $this->apiInstance->getEnvironmentId(),
            'serveboltcdn'
        );
        $this->debugRequest('serveboltcdn', __FUNCTION__, [
            'target' => 'serveboltcdn',
        ], $response);
        if ($response->wasSuccessful()) {
            return true;
        } else {
           throw new ServeboltApiError($response->getErrors(), 'serveboltcdn');
        }
    }

    /**
     * Purge PHP OpCache for the environment.
     *
     * @return bool
     * @throws ServeboltApiError
     */
    public function purgeOpCache(): bool
    {
        $response = $this->apiInstance->environment->purgeOpCache(
            $this->apiInstance->getEnvironmentId()
        );
        $this->debugRequest('serveboltcdn', __FUNCTION__, [], $response);
        if ($response->wasSuccessful()) {
            return true;
        } else {
            throw new ServeboltApiError($response->getErrors(), $response, 'serveboltcdn');
        }
    }

    /**
     * 
     * @param array $tags : array of tags to be delted 
     * @return bool
     * @throws ServeboltApiError
     */
    public function purgeByTags(array $tags = []) : bool
    {
        $response = $this->apiInstance->environment->purgeCdnCache(
            $this->apiInstance->getEnvironmentId(),
            [], // files
            [], // prefixes
            $tags, // tags
            // hosts
            
        );
        $this->debugRequest('serveboltcdn', __FUNCTION__, [
            'files' => [],
            'prefixes' => [],
            'tags' => $tags,
        ], $response);
        if ($response->wasSuccessful()) {
            return true;
        } else {
            throw new ServeboltApiError($response->getErrors(), $response, 'serveboltcdn');
        }
    }


    /**
     * Purge CDN cache for all sites in multisite-network.
     *
     * @return bool
     * @throws ServeboltApiError
     */
    public function purgeAllNetwork(): bool
    {
        $prefixes = $this->getPurgeAllPrefixesWithMultisiteSupport();
        $response = $this->apiInstance->environment->purgeCdnCache(
            $this->apiInstance->getEnvironmentId(),
            [], // files
            $prefixes
            // tags
            // hosts
        );
        $this->debugRequest('serveboltcdn', __FUNCTION__, [
            'files' => [],
            'prefixes' => $prefixes,
        ], $response);
        if ($response->wasSuccessful()) {
            return true;
        } else {
            throw new ServeboltApiError($response->getErrors(), $response, 'serveboltcdn');
        }
    }
}
