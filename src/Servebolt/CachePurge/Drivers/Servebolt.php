<?php

namespace Servebolt\Optimizer\CachePurge\Drivers;

use Servebolt\Optimizer\Api\Servebolt\Servebolt as ServeboltSdk;
use Servebolt\Optimizer\Traits\Singleton;
use Servebolt\Optimizer\CachePurge\Interfaces\CachePurgeInterface;

class Servebolt implements CachePurgeInterface
{
    use Singleton;

    /**
     * @param string $url
     * @return mixed
     * @throws \ReflectionException
     */
    public function purgeByUrl(string $url)
    {
        $instance = ServeboltSdk::getInstance();
        return $instance->environment()->purgeCache([$url]);
    }

    /**
     * @param array $urls
     * @return mixed
     * @throws \ReflectionException
     */
    public function purgeByUrls(array $urls)
    {
        $instance = ServeboltSdk::getInstance();
        return $instance->environment->purgeCache($instance->getEnvironmentId(), $urls);
    }

    /**
     * @return mixed
     * @throws \ReflectionException
     */
    public function purgeAll()
    {
        $instance = ServeboltSdk::getInstance();
        return $instance->environment->purgeCache($instance->getEnvironmentId(), [], $this->getPurgeAllPrefixes());
    }

    /**
     * Build array of prefix URLs when purging all cache for a site.
     *
     * @return array
     */
    private function getPurgeAllPrefixes() : array
    {
        $prefixes = [];
        // TODO: If multisite then get all domains in multisite, if singlesite then just give the URL of the site. Filter allows for people to fix own edge cases.
        return apply_filters('sb_optimizer_acd_purge_all_prefixes', $prefixes);
    }
}
