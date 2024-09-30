<?php

namespace Servebolt\Optimizer\CachePurge\Drivers;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\CachePurge\Interfaces\CachePurgeAllInterface;
use Servebolt\Optimizer\CachePurge\Interfaces\CachePurgeUrlInterface;
use Servebolt\Optimizer\CachePurge\Interfaces\CachePurgeTagInterface;
use Servebolt\Optimizer\Traits\Singleton;
use Servebolt\Optimizer\Exceptions\ServeboltApiError;
use function Servebolt\Optimizer\Helpers\getDomainNameOfWebSite;

/**
 * Class ServeboltCdn
 * @package Servebolt\Optimizer\CachePurge\Drivers
 */
class ServeboltCdn implements CachePurgeAllInterface, CachePurgeTagInterface, CachePurgeUrlInterface
{
    use Singleton, ServeboltDriverTrait;

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
     * Purge CDN cache (for a single site).
     *
     * @return bool
     * @throws ServeboltApiError
     */
    public function purgeAll(): bool
    {
        $response = $this->apiInstance->environment->purgeCache(
            $this->apiInstance->getEnvironmentId(),
            [], // files
            $this->getPurgeAllPrefixes() // prefixes
            // tags
            // hosts
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
        $response = $this->apiInstance->environment->purgeCdnCache(
            $this->apiInstance->getEnvironmentId(),
            [], // hosts
            $tags, // array of tags
            
        );
        if ($response->wasSuccessful()) {
            return true;
        } else {
            throw new ServeboltApiError($response->getErrors(), $response);
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
        $response = $this->apiInstance->environment->purgeCdnCache(
            $this->apiInstance->getEnvironmentId(),
            $this->getPurgeAllPrefixesWithMultisiteSupport()
        );
        if ($response->wasSuccessful()) {
            return true;
        } else {
            throw new ServeboltApiError($response->getErrors(), $response);
        }
    }
}
