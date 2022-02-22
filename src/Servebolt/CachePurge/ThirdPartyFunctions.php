<?php

use Servebolt\Optimizer\CachePurge\WordPressCachePurge\WordPressCachePurge;
use Servebolt\Optimizer\Exceptions\ApiError;

/**
 * Purge cache for URL.
 *
 * @param string $url The URL to purge cache for.
 * @param bool $return_wp_error_object Whether to return WP_Error-object on error.
 * @return bool|WP_Error
 */
function sb_purge_url($url, bool $return_wp_error_object = false)
{
    try {
        return WordPressCachePurge::purgeByUrl((string) $url);
    } catch (ApiError $e) {
        if ($return_wp_error_object) {
            // TODO: Return WP Error object
        }
        return false;
    } catch (Exception $e) {
        if ($return_wp_error_object) {
            // TODO: Return WP Error object
        }
        return false;
    }
}

/**
 * Purge cache for post.
 *
 * @param int $post_id The ID of the post to purge cache for.
 * @param bool $return_wp_error_object Whether to return WP_Error-object on error.
 * @return bool|WP_Error
 */
function sb_purge_post_cache($post_id, bool $return_wp_error_object = false)
{
    try {
        return WordPressCachePurge::purgeByPostId(
            (int) $post_id
        );
    } catch (ApiError $e) {
        // TODO: Handle API error message
        if ($return_wp_error_object) {
            // TODO: Return WP Error object
        }
        return false;
    } catch (Exception $e) {
        // TODO: Handle general error
        if ($return_wp_error_object) {
            // TODO: Return WP Error object
        }
        return false;
    }
    return false;
}

/**
 * Purge cache for term.
 *
 * @param int $term_id The ID of the term to purge cache for.
 * @param string $taxonomy_slug The slug of the taxonomy of the term.
 * @param bool $return_wp_error_object Whether to return WP_Error-object on error.
 * @return bool|WP_Error
 */
function sb_purge_term_cache($term_id, $taxonomy_slug, bool $return_wp_error_object = false)
{
    try {
        return WordPressCachePurge::purgeByTermId(
            (int) $term_id,
            (string) $taxonomy_slug
        );
    } catch (ApiError $e) {
        // TODO: Handle API error message
        if ($return_wp_error_object) {
            // TODO: Return WP Error object
        }
        return false;
    } catch (Exception $e) {
        // TODO: Handle general error
        if ($return_wp_error_object) {
            // TODO: Return WP Error object
        }
        return false;
    }
    return false;
}

/**
 * Purge all cache.
 *
 * @param bool $return_wp_error_object Whether to return WP_Error-object on error.
 * @return bool|WP_Error
 */
function sb_purge_all(bool $return_wp_error_object = false)
{
    try {
        return WordPressCachePurge::purgeAll();
    } catch (ApiError $e) {
        // TODO: Handle API error message
        if ($return_wp_error_object) {
            // TODO: Return WP Error object
        }
        return false;
    } catch (Exception $e) {
        // TODO: Handle general error
        if ($return_wp_error_object) {
            // TODO: Return WP Error object
        }
        return false;
    }
}

/**
 * Purge all cache by blog Id (multisite only).
 *
 * @param int $blog_id The ID of the blog to purge all cache for.
 * @param bool $return_wp_error_object Whether to return WP_Error-object on error.
 * @return false|WP_Error
 */
function sb_purge_all_by_blog_id($blog_id, bool $return_wp_error_object = false)
{
    if (!is_multisite()) {
        return false;
    }
    try {
        return WordPressCachePurge::purgeAllByBlogId((int) $blog_id);
    } catch (ApiError $e) {
        // TODO: Handle API error message
        if ($return_wp_error_object) {
            // TODO: Return WP Error object
        }
        return false;
    } catch (Exception $e) {
        // TODO: Handle general error
        if ($return_wp_error_object) {
            // TODO: Return WP Error object
        }
        return false;
    }
}

/**
 * Purge all cache in network (multisite only).
 *
 * @param bool $return_wp_error_object Whether to return WP_Error-object on error.
 * @return bool|WP_Error
 */
function sb_purge_all_network(bool $return_wp_error_object = false)
{
    if (!is_multisite()) {
        return false;
    }
    try {
        return WordPressCachePurge::purgeAllNetwork();
    } catch (ApiError $e) {
        // TODO: Handle API error message
        if ($return_wp_error_object) {
            // TODO: Return WP Error object
        }
        return false;
    } catch (Exception $e) {
        // TODO: Handle general error
        if ($return_wp_error_object) {
            // TODO: Return WP Error object
        }
        return false;
    }

}
