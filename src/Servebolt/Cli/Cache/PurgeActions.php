<?php

namespace Servebolt\Optimizer\Cli\Cache;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use WP_CLI;
use Servebolt\Optimizer\Cli\CliHelpers;
use Servebolt\Optimizer\CachePurge\WordPressCachePurge\WordPressCachePurge;
#use Servebolt\Optimizer\Exceptions\ApiError;
use Exception;
use function Servebolt\Optimizer\Helpers\arrayGet;

/**
 * Class CacheSettings
 * @package Servebolt\Optimizer\Cli\Cache
 */
class PurgeActions
{

    /**
     * CacheSettings constructor.
     */
    public function __construct()
    {
        WP_CLI::add_command('servebolt cache purge url', [$this, 'purgeUrl']);
        WP_CLI::add_command('servebolt cache purge urls', [$this, 'purgeUrls']);
        WP_CLI::add_command('servebolt cache purge post', [$this, 'purgePost']);
        WP_CLI::add_command('servebolt cache purge term', [$this, 'purgeTerm']);
        WP_CLI::add_command('servebolt cache purge all', [$this, 'purgeAll']);
    }

    /**
     * Purges cache for URL.
     *
     * ## OPTIONS
     *
     * <URL>
     * : The URL to be purged cache for.
     *
     * ## EXAMPLES
     *
     *     wp servebolt cache purge url https://some-url.com/some-path/
     */
    public function purgeUrl(array $args, array $assocArgs): void
    {
        list($url) = $args;
        try {
            if (WordPressCachePurge::purgeByUrl($url)) {
                WP_CLI::success(sprintf(__('Cache purged for URL "%s".', 'servebolt-wp'), $url));
            } else {
                WP_CLI::error(sprintf(__('Could not purge cache for URL "%s".', 'servebolt-wp'), $url));
            }
        } catch (Exception $e) {
            // TODO: Handle error better
            WP_CLI::error(sprintf(__('Could not purge cache for URL "%s".', 'servebolt-wp'), $url));
        }
    }

    /**
     * Purges cache for URLs.
     *
     * ## OPTIONS
     *
     * <URLs>
     * : Comma separated string with URLs to be purged cache for.
     *
     * [--delimiter=<delimiter>]
     * : The character used to split the string into multiple URLs. Defaults to ",".
     *
     * ## EXAMPLES
     *
     *      wp servebolt cache purge urls https://some-url.com/some-path/,https://some-url.com/some-other-path/
     *
     *      wp servebolt cache purge urls --delimiter="/" https://some-url.com/some-path/|https://some-url.com/some-other-path/
     */
    public function purgeUrls(array $args, array $assocArgs): void
    {
        list($urls) = $args;
        $delimiter = arrayGet('delimiter', $assocArgs) ?: ',';
        $urls = array_map(function($url) {
            return trim($url);
        }, explode($delimiter, $urls));
        try {
            if (WordPressCachePurge::purgeByUrls($urls)) {
                WP_CLI::success(sprintf(__('Cache purged for %s URLs.', 'servebolt-wp'), count($urls)));
            } else {
                WP_CLI::error(sprintf(__('Could not purge cahce for %s URLs.', 'servebolt-wp'), count($urls)));
            }
        } catch (ApiError|Exception $e) {
            // TODO: Handle error better
            WP_CLI::error(sprintf(__('Could not purge cahce for %s URLs.', 'servebolt-wp'), count($urls)));
        }
    }

    /**
     * Purges cache for post.
     *
     * ## OPTIONS
     *
     * <postId>
     * : The Id of the post to be purged.
     *
     * ## EXAMPLES
     *
     *     # Purge post with ID 1
     *     wp servebolt cache purge post 1
     */
    public function purgePost(array $args, array $assocArgs): void
    {
        list($postId) = $args;
        try {
            if (WordPressCachePurge::purgeByPostId(
                (int) $postId
            )) {
                WP_CLI::success(sprintf(__('Cache purged for post "%s" (ID %s).', 'servebolt-wp'), get_the_title($postId), $postId));
            } else {
                WP_CLI::error(sprintf(__('Could not purge cache for post "%s" (ID %s).', 'servebolt-wp'), get_the_title($postId), $postId));
            }
        } catch (ApiError|Exception $e) {
            // TODO: Handle error better
            WP_CLI::error(sprintf(__('Could not purge cache for post "%s" (ID %s).', 'servebolt-wp'), get_the_title($postId), $postId));
        }
    }

    /**
     * Purges cache for term.
     *
     * ## OPTIONS
     *
     * <termId>
     * : The Id of the term to be purged.
     *
     * <taxonomySlug>
     * : The taxonomy slug.
     *
     * ## EXAMPLES
     *
     *     # Purge term with ID 1 in category-taxonomy
     *     wp servebolt cache purge term 1 category
     */
    public function purgeTerm(array $args, array $assocArgs): void
    {
        list($termId, $taxonomySlug) = $args;
        $termName = get_term($termId)->name;
        try {
            if (WordPressCachePurge::purgeTermCache(
                (int) $termId,
                $taxonomySlug
            )) {
                WP_CLI::success(sprintf(__('Cache purged for term "%s" (ID %s).', 'servebolt-wp'), $termName, $termId));
            } else {
                WP_CLI::error(sprintf(__('Could not purge cache for term "%s" (ID %s).', 'servebolt-wp'), $termName, $termId));
            }
        } catch (ApiError|Exception $e) {
            // TODO: Handle error better
            WP_CLI::error(sprintf(__('Could not purge cache for term "%s" (ID %s).', 'servebolt-wp'), $termName, $termId));
        }
    }

    /**
     * Purges all cache.
     *
     * ## OPTIONS
     *
     * [--all]
     * : Display the setting for all sites.
     *
     * ## EXAMPLES
     *
     *     wp servebolt cache purge all
     */
    public function purgeAll(array $args, array $assocArgs): void
    {
        try {
            if (CliHelpers::affectAllSites($assocArgs)) {
                if (WordPressCachePurge::purgeAllNetwork()) {
                    WP_CLI::success(__('All cache purged for all sites in multisite-network.', 'servebolt-wp'));
                } else {
                    WP_CLI::success(__('Could not purge cache. Make sure feature is configured properly.', 'servebolt-wp'));
                }
            } else {
                if (WordPressCachePurge::purgeAll()) {
                    WP_CLI::success(__('All cache purged.', 'servebolt-wp'));
                } else {
                    WP_CLI::error(__('Could not purge cache. Make sure feature is configured properly.', 'servebolt-wp'));
                }
            }
        } catch (ApiError|Exception $e) {
            // TODO: Handle error better
            WP_CLI::error(__('Could not purge cache.', 'servebolt-wp'));
        }
    }
}
