<?php

// Third party developer-friendly functions for purging cache

use Servebolt\Optimizer\CachePurge\CachePurge as CachePurgeDriver;

/**
 * Purge cache for post.
 *
 * @param $post_id
 * @return bool
 * @throws ReflectionException
 */
function sb_purge_post_cache($post_id) : bool
{
    $driver = CachePurgeDriver::getInstance();
    return $driver->purgeCacheForPost($post_id);
}

/**
 * Purge cache for term.
 *
 * @param $term_id
 * @return bool
 * @throws ReflectionException
 */
function sb_purge_post_term($term_id) : bool
{
    $driver = CachePurgeDriver::getInstance();
    return $driver->purgeCacheForTerm($term_id);
}

/**
 * Purge all cache.
 *
 * NOTE: Only available when using Cloudflare cache purging.
 *
 * @return bool
 * @throws ReflectionException
 */
function sb_purge_all() : bool
{
    $driver = CachePurgeDriver::getInstance();
    if (method_exists($driver, 'purgeAll')) {
        return $driver->purgeAll();
    }
    return false;
}
