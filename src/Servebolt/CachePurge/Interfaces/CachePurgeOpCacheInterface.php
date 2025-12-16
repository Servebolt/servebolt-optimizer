<?php

namespace Servebolt\Optimizer\CachePurge\Interfaces;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

interface CachePurgeOpCacheInterface
{
    /**
     * Purge PHP OpCache on the Servebolt environment.
     *
     * @return bool
     */
    public function purgeOpCache();
}
