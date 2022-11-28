<?php

namespace Servebolt\Optimizer\CachePurge\Drivers;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\CachePurge\Interfaces\CachePurgeAllInterface;
use Servebolt\Optimizer\Traits\Singleton;
use Servebolt\Optimizer\Exceptions\ServeboltApiError;
use Servebolt\Optimizer\CachePurge\Interfaces\CachePurgeTagInterface;

/**
 * Class ServeboltCdn
 * @package Servebolt\Optimizer\CachePurge\Drivers
 */
class ServeboltCdn implements CachePurgeAllInterface, CachePurgeTagInterface
{
    use Singleton, ServeboltDriverTrait;

    /**
     * Purge CDN cache (for a single site).
     *
     * @return bool
     * @throws ServeboltApiError
     */
    public function purgeAll(): bool
    {
        $response = $this->apiInstance->environment->purgeCdnCache(
            $this->apiInstance->getEnvironmentId(),
            $this->getPurgeAllPrefixes()
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
     * @param array $hosts : array of domains to purge
     * @return bool
     * @throws ServeboltApiError
     */
    public function purgeByTags(array $tags = [], array $hosts = []) : bool
    {
        $response = $this->apiInstance->environment->purgeCache(
            $this->apiInstance->getEnvironmentId(),
            [], // files urls
            [], // prefixes
            $tags, // array of tags
            $hosts // array of hosts
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
