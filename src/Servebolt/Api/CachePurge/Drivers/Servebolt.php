<?php

namespace Servebolt\Optimizer\Api\CachePurge\Drivers;

use Servebolt\Sdk\Client as ServeboltSdk;
use Servebolt\Optimizer\Traits\Multiton;
use Servebolt\Optimizer\Api\CachePurge\Interfaces\CachePurgeInterface;

class Servebolt implements CachePurgeInterface
{
    use Multiton;

    /**
     * @param string $url
     * @return mixed
     */
    public function purgeByUrl(string $url)
    {
        ServeboltSdk::
        return $this->client->environment($this->configArguments['environmentId'])->purgeCache([$url]);
    }

    /**
     * @param array $urls
     * @return mixed
     */
    public function purgeByUrls(array $urls)
    {
        return $this->client->environment($this->configArguments['environmentId'])->purgeCache($url);
    }
}
