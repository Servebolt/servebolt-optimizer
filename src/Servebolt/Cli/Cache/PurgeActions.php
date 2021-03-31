<?php

namespace Servebolt\Optimizer\Cli\Cache;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use WP_CLI;
use Servebolt\Optimizer\Cli\CliHelpers;
use Servebolt\Optimizer\CachePurge\WordPressCachePurge\WordPressCachePurge;
use Servebolt\Optimizer\Exceptions\ApiError;
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
            WordPressCachePurge::purgeByUrl($url);
            WP_CLI::success(sprintf(__('Cache purged for URL "%s".', 'servebolt-wp'), $url));
        } catch (ApiError $e) {
            // TODO: Handle error
        } catch (Exception $e) {
            // TODO: Handle error
        }
    }

    /**
     * Purges cache for URLs.
     *
     * ## OPTIONS
     *
     * <URLs>
     * : Comma separated strign with URLs to be purged cache for.
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
        die($delimiter);
        $urls = array_map(function($url) {
            return trim($url);
        }, explode($delimiter, $urls));
        try {
            WordPressCachePurge::purgeByUrls($urls);
            WP_CLI::success(sprintf(__('Cache purged for %s URLs.', 'servebolt-wp'), count($urls)));
        } catch (ApiError $e) {
            // TODO: Handle error
        } catch (Exception $e) {
            // TODO: Handle error
        }
    }

    public function purgePost(array $args, array $assocArgs): void
    {
        list($postId) = $args;
        try {
            WordPressCachePurge::purgeByPostId(
                (int) $postId
            );
            WP_CLI::success(sprintf(__('Cache purged for post "%s" (ID %s).', 'servebolt-wp'), get_the_title($postId), $postId));
        } catch (ApiError $e) {
            // TODO: Handle error
        } catch (Exception $e) {
            // TODO: Handle error
        }
    }

    public function purgeTerm(array $args, array $assocArgs): void
    {
        list($termId, $taxonomySlug) = $args;
        try {
            WordPressCachePurge::purgeTermCache(
                (int) $termId,
                $taxonomySlug
            );
            $termName = get_term($termId)->name;
            WP_CLI::success(sprintf(__('Cache purged for term "%s" (ID %s).', 'servebolt-wp'), $termName, $termId));
        } catch (ApiError $e) {
            // TODO: Handle error
        } catch (Exception $e) {
            // TODO: Handle error
        }
    }

    public function purgeAll(array $args, array $assocArgs): void
    {
        $affectAllSites = CliHelpers::affectAllSites($assocArgs);
        try {
            if ($affectAllSites) {
                WordPressCachePurge::purgeAllNetwork();
                WP_CLI::success(__('All cache purged for all sites in multisite-network.', 'servebolt-wp'));
            } else {
                WordPressCachePurge::purgeAll();
                WP_CLI::success(__('All cache purged.', 'servebolt-wp'));
            }
        } catch (ApiError $e) {
            // TODO: Handle error
        } catch (Exception $e) {
            // TODO: Handle error
        }
    }
}
