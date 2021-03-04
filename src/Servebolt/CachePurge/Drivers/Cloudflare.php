<?php

namespace Servebolt\Optimizer\CachePurge\Drivers;

use Servebolt\Optimizer\Api\Cloudflare\Cloudflare as CloudflareApi;
use Servebolt\Optimizer\Traits\Singleton;
use Servebolt\Optimizer\CachePurge\Interfaces\CachePurgeInterface;

class Cloudflare implements CachePurgeInterface
{
    use Singleton;

    /**
     * @param string $url
     * @return mixed
     * @throws \ReflectionException
     */
    public function purgeByUrl(string $url)
    {
        $instance = CloudflareApi::getInstance();
        return $instance->purgeUrl([$url]);
    }

    /**
     * @param array $urls
     * @return mixed
     * @throws \ReflectionException
     */
    public function purgeByUrls(array $urls)
    {
        $instance = CloudflareApi::getInstance();
        return $instance->purgeUrl($urls);
    }

    /**
     * @return mixed
     * @throws \ReflectionException
     */
    public function purgeAll()
    {
        $instance = CloudflareApi::getInstance();
        return $instance->purgeAll();
    }
}
