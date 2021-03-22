<?php

namespace Servebolt\Optimizer\Cli\Cache;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use WP_CLI;
use Servebolt\Optimizer\Cli\CliHelpers;
use Servebolt\Optimizer\CachePurge\WordPressCachePurge\WordPressCachePurge;
use Servebolt\Optimizer\Exceptions\ApiError;

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
        WP_CLI::add_command('servebolt cache purge post', [$this, 'purgePost']);
        WP_CLI::add_command('servebolt cache purge term', [$this, 'purgeTerm']);
        WP_CLI::add_command('servebolt cache purge all', [$this, 'purgeAll']);
    }

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
