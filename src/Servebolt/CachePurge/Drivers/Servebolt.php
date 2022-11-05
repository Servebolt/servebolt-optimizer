<?php

namespace Servebolt\Optimizer\CachePurge\Drivers;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Traits\Singleton;
use Servebolt\Optimizer\CachePurge\Interfaces\CachePurgeUrlInterface;
use Servebolt\Optimizer\CachePurge\Interfaces\CachePurgeAllInterface;
use Servebolt\Optimizer\CachePurge\Interfaces\CachePurgePrefixInterface;
use Servebolt\Optimizer\Exceptions\ServeboltApiError;

/**
 * Class Servebolt
 * @package Servebolt\Optimizer\CachePurge\Drivers
 */
class Servebolt implements CachePurgeAllInterface, CachePurgeUrlInterface, CachePurgePrefixInterface, CachePurgeTagInterface
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
     * 
     * @param array $tags : array of tags to be delted 
     * @param array $domain : 
     * @return bool
     * @throws ServeboltApiError
     */
    public function purgeByTag(array $tags, string $domain) : bool
    {
        $response = $this->apiInstance->environment->purgeCache(
            $this->apiInstance->getEnvironmentId(),
            $tags
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
    public function purgeByPrefix(string $prefix): bool
    {
        // TODO: purge prefix...
        return true;
        // $response = $this->apiInstance->environment->purgeCache(
        //     $this->apiInstance->getEnvironmentId(),
        //     $urls
        // );
        // if ($response->wasSuccessful()) {
        //     return true;
        // } else {
        //     throw new ServeboltApiError($response->getErrors(), $response);
        // }
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
