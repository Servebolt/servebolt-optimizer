<?php

namespace Servebolt\Optimizer\CachePurge\Drivers;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Traits\Singleton;
use Servebolt\Optimizer\Exceptions\ServeboltApiError;

class ServeboltCdn
{
    use Singleton, ServeboltDriverTrait;

    /**
     * Purge CDN cache (for a single site).
     *
     * @return bool
     * @throws ServeboltApiError
     */
    public function purgeCdn(): bool
    {
        $response = $this->apiInstance->environment->purgeCdnCache(
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
     * Purge CDN cache for all sites in multisite-network.
     *
     * @return bool
     * @throws ServeboltApiError
     */
    public function purgeCdnNetwork(): bool
    {
        $response = $this->apiInstance->environment->purgeCdnCache(
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
