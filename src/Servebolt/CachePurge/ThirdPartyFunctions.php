<?php

use Servebolt\Optimizer\CachePurge\WordPressCachePurge\WordPressCachePurge;

/**
 * Purge cache for URL.
 *
 * @param string $url
 * @return bool
 */
function sb_purge_url(string $url): bool
{
    return WordPressCachePurge::purgeByUrl($url);
}

/**
 * Purge cache for post.
 *
 * @param $post_id
 * @return bool
 */
function sb_purge_post_cache($post_id) : bool
{
    return WordPressCachePurge::purgeByPostId((int) $post_id);
}

/**
 * Purge cache for term.
 *
 * @param $term_id
 * @return bool
 */
function sb_purge_post_term($term_id) : bool
{
    return WordPressCachePurge::purgeByTermId((int) $term_id);
}

/**
 * Purge all cache.
 *
 * @return bool
 */
function sb_purge_all() : bool
{
    return WordPressCachePurge::purgeAll();
}
