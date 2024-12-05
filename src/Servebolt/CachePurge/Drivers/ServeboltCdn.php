<?php

namespace Servebolt\Optimizer\CachePurge\Drivers;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\CachePurge\Interfaces\CachePurgeAllInterface;
use Servebolt\Optimizer\CachePurge\Interfaces\CachePurgeUrlInterface;
use Servebolt\Optimizer\CachePurge\Interfaces\CachePurgeTagInterface;
use Servebolt\Optimizer\CachePurge\Interfaces\CachePurgeValidateUrlCandidate;
use Servebolt\Optimizer\Traits\Singleton;
use Servebolt\Optimizer\Exceptions\ServeboltApiError;
use function Servebolt\Optimizer\Helpers\getDomainNameOfWebSite;

/**
 * Class ServeboltCdn
 * @package Servebolt\Optimizer\CachePurge\Drivers
 */
class ServeboltCdn implements CachePurgeAllInterface, CachePurgeTagInterface, CachePurgeUrlInterface, CachePurgeValidateUrlCandidate
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
            '/wp-login.php',
            '/wp-cron.php',
            '/xmlrpc.php',
            '/index.php/',
            '/wp-comments-post.php',
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
        $response = $this->apiInstance->environment->purgeCdnCache(
            $this->apiInstance->getEnvironmentId(),
            [], // files
            $this->getPurgeAllPrefixes() // prefixes
            // tags
            // hosts
        );
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
        $response = $this->apiInstance->environment->purgeCdnCache(
            $this->apiInstance->getEnvironmentId(),
            [], // files
            $this->getPurgeAllPrefixesWithMultisiteSupport()
            // tags
            // hosts
        );
        if ($response->wasSuccessful()) {
            return true;
        } else {
            throw new ServeboltApiError($response->getErrors(), $response, 'serveboltcdn');
        }
    }
}
