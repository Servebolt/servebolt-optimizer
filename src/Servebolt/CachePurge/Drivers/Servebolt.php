<?php

namespace Servebolt\Optimizer\CachePurge\Drivers;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Traits\Singleton;
use Servebolt\Optimizer\CachePurge\Interfaces\CachePurgeUrlInterface;
use Servebolt\Optimizer\CachePurge\Interfaces\CachePurgeAllInterface;
use Servebolt\Optimizer\CachePurge\Interfaces\CachePurgePrefixInterface;
use Servebolt\Optimizer\CachePurge\Interfaces\CachePurgeTagInterface;
use Servebolt\Optimizer\CachePurge\Interfaces\CachePurgeValidateUrlCandidate;
use Servebolt\Optimizer\CachePurge\Interfaces\CachePurgeServerInterface;
use Servebolt\Optimizer\CachePurge\Interfaces\CachePurgeOpCacheInterface;
use Servebolt\Optimizer\Exceptions\ServeboltApiError;
use function Servebolt\Optimizer\Helpers\getDomainNameOfWebSite;

/**
 * Class Servebolt
 * @package Servebolt\Optimizer\CachePurge\Drivers
 */
class Servebolt implements CachePurgeAllInterface, CachePurgeUrlInterface, CachePurgePrefixInterface, CachePurgeTagInterface, CachePurgeValidateUrlCandidate, CachePurgeServerInterface, CachePurgeOpCacheInterface
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
        $response = $this->apiInstance->environment()->purgeCache(
            $this->apiInstance->getEnvironmentId(),
            [$url]
        );
        if ($response->wasSuccessful()) {
            return true;
        } else {
            throw new ServeboltApiError($response->getErrors(), $response);
        }
    }

    /**
     * @param array $urls
     * @return bool
     * @throws ServeboltApiError
     */
    public function purgeByUrls(array $urls): bool
    {
        $response = $this->apiInstance->environment->purgeCache(
            $this->apiInstance->getEnvironmentId(),
            $urls
        );
        if ($response->wasSuccessful()) {
            return true;
        } else {
            throw new ServeboltApiError($response->getErrors(), $response);
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
        $response = $this->apiInstance->environment->purgeCache(
            $this->apiInstance->getEnvironmentId(),
            [], // files urls
            [], // prefixes
            $tags, // array of tags
            [] // hosts
        );
        if ($response->wasSuccessful()) {
            return true;
        } else {
            throw new ServeboltApiError($response->getErrors(), $response);
        }
    }

    /**
     * @param string $prefix
     * @return bool
     * @throws ServeboltApiError
     */
    public function purgeByPrefix(string $prefix): bool
    {
        $response = $this->apiInstance->environment->purgeCache(
            $this->apiInstance->getEnvironmentId(),
            [], // files urls
            [$prefix]            
        );
        if ($response->wasSuccessful()) {
            return true;
        } else {
            throw new ServeboltApiError($response->getErrors(), $response);
        }
    }

    /**
     * @param array $prefixes
     * @return bool
     * @throws ServeboltApiError
     */
    public function purgeByPrefixes( array $prefixes): bool
    {
        $response = $this->apiInstance->environment->purgeCache(
            $this->apiInstance->getEnvironmentId(),
            [], // files urls
            $prefixes
        );
        if ($response->wasSuccessful()) {
            return true;
        } else {
            throw new ServeboltApiError($response->getErrors(), $response);
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
            'acd'
        );
        if ($response->wasSuccessful()) {
            return true;
        } else {
           throw new ServeboltApiError($response->getErrors(), $response);
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
        if ($response->wasSuccessful()) {
            return true;
        } else {
            throw new ServeboltApiError($response->getErrors(), $response);
        }
    }

    /**
     * Purge all cache (for a single site).
     *
     * @return bool
     * @throws ServeboltApiError
     */
    public function purgeAll(): bool
    {
        $response = $this->apiInstance->environment->purgeCache(
            $this->apiInstance->getEnvironmentId(),
            [],
            $this->getPurgeAllPrefixes()
        );
        if ($response->wasSuccessful()) {
            return true;
        } else {
           throw new ServeboltApiError($response->getErrors(), $response);
        }
    }

    /**
     * Purge cache for all sites in multisite-network.
     *
     * @return bool
     * @throws ServeboltApiError
     */
    public function purgeAllNetwork(): bool
    {
        $response = $this->apiInstance->environment->purgeCache(
            $this->apiInstance->getEnvironmentId(),
            [],
            $this->getPurgeAllPrefixesWithMultisiteSupport()
        );
        if ($response->wasSuccessful()) {
            return true;
        } else {
            throw new ServeboltApiError($response->getErrors(), $response);
        }
    }
}
