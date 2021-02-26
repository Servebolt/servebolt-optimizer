<?php

namespace Servebolt\Optimizer\Api\CachePurge\Interfaces;

interface CachePurgeInterface
{
    public function purgeByUrl(string $url);
    public function purgeByUrls(array $urls);
}
