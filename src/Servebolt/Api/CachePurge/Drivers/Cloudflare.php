<?php

namespace Servebolt\Optimizer\Api\CachePurge\Drivers;

use Servebolt\Optimizer\Api\Sdk\Cloudflare as CloudFlareSdk;
use Servebolt\Optimizer\Traits\Multiton;
use Servebolt\Optimizer\Api\CachePurge\Interfaces\CachePurgeInterface;

class Cloudflare implements CachePurgeInterface
{
    use Multiton;

    private $client;

    public function __construct()
    {
        $this->client = new CloudFlareSdk;
    }

    /**
     * @param string $url
     * @return mixed
     */
    public function purgeByUrl(string $url)
    {
        $instance = CloudFlareSdk::getInstance();
    }

    /**
     * @param array $urls
     * @return mixed
     */
    public function purgeByUrls(array $urls)
    {

    }
}
