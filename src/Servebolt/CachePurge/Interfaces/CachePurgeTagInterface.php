<?php

namespace Servebolt\Optimizer\CachePurge\Interfaces;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

interface CachePurgeTagInterface
{
    public function purgeByTag(array $tags, string $domain);
}
