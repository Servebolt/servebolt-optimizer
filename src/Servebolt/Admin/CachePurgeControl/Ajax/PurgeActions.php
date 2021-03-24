<?php

namespace Servebolt\Optimizer\Admin\CachePurgeControl\Ajax;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\CachePurge\WordPressCachePurge\WordPressCachePurge;
use Servebolt\Optimizer\CachePurge\CachePurge;
use Servebolt\Optimizer\Admin\SharedAjaxMethods;
use Exception;
use Servebolt\Optimizer\Exceptions\ApiError;
use Servebolt\Optimizer\Exceptions\ApiMessage;
use Servebolt\Optimizer\Exceptions\QueueError;
use function Servebolt\Optimizer\Helpers\arrayGet;
use function Servebolt\Optimizer\Helpers\postExists;
use function Servebolt\Optimizer\Helpers\ajaxUserAllowed;
//use function Servebolt\Optimizer\Helpers\getBlogName;
//use function Servebolt\Optimizer\Helpers\createLiTagsFromArray;
//use function Servebolt\Optimizer\Helpers\requireSuperadmin;
//use function Servebolt\Optimizer\Helpers\countSites;
//use function Servebolt\Optimizer\Helpers\iterateSites;

class PurgeActions extends SharedAjaxMethods
{

    /**
     * CachePurgeActions constructor.
     */
    public function __construct()
    {
        add_action('wp_ajax_servebolt_purge_all_cache', [$this, 'purgeAllCacheCallback']);
        add_action('wp_ajax_servebolt_purge_url_cache', [$this, 'purgeUrlCacheCallback']);
        add_action('wp_ajax_servebolt_purge_post_cache', [$this, 'purgePostCacheCallback']);
        /*
        // TODO: Make this feature work with the new cache purge driver
        if ( is_multisite() ) {
            add_action('wp_ajax_servebolt_purge_network_cache', [$this, 'purgeNetworkCacheCallback']);
        }
        */
    }

    /**
     * Ensure that the cache purge feature is active, unless send an error response.
     */
    private function ensureCachePurgeFeatureIsActive(): void
    {
        if (!CachePurge::isActive()) {
            wp_send_json_error(
                [
                    'message' => 'The cache purge feature is not active so we could not purge cache. Make sure you the configuration is correct.'
                ]
            );
        }
    }

    /**
     * Purge all cache cache.
     */
    public function purgeAllCacheCallback()
    {
        $this->checkAjaxReferer();
        ajaxUserAllowed();

        $this->ensureCachePurgeFeatureIsActive();

        $cronPurgeIsActive = CachePurge::cronPurgeIsActive();
        $hasPurgeAllRequestInQueue = false; // TODO: Check if there is already a purge all request in the queue, if the cron feature is active.

        if ($cronPurgeIsActive && $hasPurgeAllRequestInQueue) {
            wp_send_json_success([
                'title' => 'All good!',
                'message' => 'A purge all-request is already queued and should be executed shortly.',
            ]);
            return;
        }

        try {
            WordPressCachePurge::purgeAll();
            if ($cronPurgeIsActive) {
                wp_send_json_success( [
                    'title' => 'Just a moment',
                    'message' => 'A purge all-request was added to the queue and will be executed shortly.',
                ] );
            } else {
                wp_send_json_success(['message' => 'All cache was purged.']);
            }
        } catch (QueueError $e) {
            // TODO: Handle response from queue system
        } catch (ApiMessage $e) {
            // TODO: Handle messages from API.
        } catch (ApiError|Exception $e) {
            $this->handleErrors($e);
        }
    }

    /**
     * Get errors and return in JSON-format.
     *
     * @param $exception
     */
    private function handleErrors($exception): void
    {
        if (method_exists($exception, 'hasMultipleErrors') && $exception->hasMultipleErrors()) {
            wp_send_json_error($exception->getErrors());
        } else {
            wp_send_json_error(['message' => $exception->getMessage()]);
        }
    }

    /**
     * Purge specific URL cache.
     */
    public function purgeUrlCacheCallback()
    {
        $this->checkAjaxReferer();
        ajaxUserAllowed();

        $this->ensureCachePurgeFeatureIsActive();

        $url = (string) arrayGet('url', $_POST);
        if (!$url || empty($url)) {
            wp_send_json_error( [ 'message' => __('Please specify the URL you would like to purge cache for.', 'servebolt-wp') ] );
            return;
        }

        $cronPurgeIsActive = CachePurge::cronPurgeIsActive();
        try {
            WordPressCachePurge::purgeByUrl($url);
            if ($cronPurgeIsActive) {
                wp_send_json_success([
                    'title' => 'Just a moment',
                    'message' => sprintf(__('A cache purge-request for the URL "%s" was added to the queue and will be executed shortly.', 'servebolt-wp'), $url),
                ]);
            } else {
                wp_send_json_success(['message' => sprintf(__('Cache was purged for URL "%s".', 'servebolt-wp'), $url)]);
            }
        } catch (QueueError $e) {
            // TODO: Handle response from queue system
        } catch (ApiMessage $e) {
            // TODO: Handle messages from API.
        } catch (ApiError $e) {
            if ($e->hasMultipleErrors()) {
                wp_send_json_error(['type' => 'error', 'messages' => $e->getErrors()]);
            } else {
                wp_send_json_error(['type' => 'error', 'message' => $e->getMessage()]);
            }
        } catch (Exception $e) {
            wp_send_json_error(['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * Purge specific post in Cloudflare cache.
     */
    public function purgePostCacheCallback() : void
    {
        $this->checkAjaxReferer();
        ajaxUserAllowed();
        $postId = intval(arrayGet('post_id', $_POST));

        $this->ensureCachePurgeFeatureIsActive();

        if (!$postId || empty($postId)) {
            wp_send_json_error(['message' => 'Please specify the post you would like to purge cache for.']);
            return;
        } elseif (!postExists($postId)) {
            wp_send_json_error(['message' => 'The specified post does not exist.']);
            return;
        }

        $cronPurgeIsActive = CachePurge::cronPurgeIsActive();
        try {
            WordPressCachePurge::purgeByPost($postId);
            if ($cronPurgeIsActive) {
                wp_send_json_success( [
                    'title'   => 'Just a moment',
                    'message' => sprintf(__('A cache purge-request for the post "%s" was added to the queue and will be executed shortly.', 'servebolt-wp'), get_the_title($postId)),
                ] );
            } else {
                wp_send_json_success(['message' => sprintf(__('Cache was purged for the post post "%s".', 'servebolt-wp'), get_the_title($postId))]);
            }
        } catch (QueueError $e) {
            // TODO: Handle response from queue system
        } catch (ApiMessage $e) {
            // TODO: Handle messages from API.
        } catch (ApiError $e) {
            if ($e->hasMultipleErrors()) {
                wp_send_json_error($e->getErrors());
            } else {
                wp_send_json_error(['message' => $e->getMessage()]);
            }
        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * Purge all Cloudflare cache in all sites in multisite-network.
     */
    /*
    public function purgeNetworkCacheCallback() : void
    {
        $this->checkAjaxReferer();
        requireSuperadmin();

        $failed_purge_attempts = [];
        $queue_based_cache_purge_sites = [];
        iterateSites(function($site) use (&$failed_purge_attempts, &$queue_based_cache_purge_sites) {

            // Switch context to blog
            if ( sb_cf_cache()->cf_switch_to_blog($site->blog_id) === false ) {
                $failed_purge_attempts[] = [
                    'blog_id' => $site->blog_id,
                    'reason'  => false,
                ];
                return;
            }

            // Skip if CF cache purge feature is not active
            if ( ! sb_cf_cache()->cf_is_active() ) {
                return;
            }

            // Check if the Cloudflare cache purg feature is avavilable
            if ( ! sb_cf_cache()->cf_cache_feature_available() ) {
                $failed_purge_attempts[] = [
                    'blog_id' => $site->blog_id,
                    'reason'  => __('Cloudflare feature not available', 'servebolt-wp'),
                ];
                return;
            }

            // Flag that current site uses queue based cache purge
            if ( sb_cf_cache()->cron_purge_is_active() ) {
                $queue_based_cache_purge_sites[] = $site->blog_id;
            }

            // Check if we already added a purge all-request to queue (if queue based cache purge is used)
            if ( sb_cf_cache()->cron_purge_is_active() && sb_cf_cache()->has_purge_all_request_in_queue() ) {
                return;
            }

            // Purge all cache
            if ( ! sb_cf_cache()->purge_all() ) {
                $failed_purge_attempts[] = [
                    'blog_id' => $site->blog_id,
                    'reason'  => false,
                ];
            }

        });

        $queue_based_cache_purge_sites_count = count($queue_based_cache_purge_sites);
        $all_sites_use_queue_based_cache_purge = $queue_based_cache_purge_sites_count == countSites();
        $some_sites_has_queue_purge_active = $queue_based_cache_purge_sites_count > 0;

        $failed_purge_attempt_count = count($failed_purge_attempts);
        $all_failed = $failed_purge_attempt_count == countSites();

        if ( $all_failed ) {
            wp_send_json_error( [
                'message' => __('Could not purge cache on any sites.', 'servebolt-wp'),
            ] );
        } else {
            if ( $failed_purge_attempt_count > 0 ) {
                wp_send_json_success([
                    'type'   => 'warning',
                    'title'  => __('Could not clear cache on all sites', 'servebolt-wp'),
                    'markup' => $this->purgeNetworkCacheFailedSites($failed_purge_attempts),
                ]);
            } else {

                if ( $all_sites_use_queue_based_cache_purge ) {
                    $feedback = __('Cache will be cleared for all sites in a moment.', 'servebolt-wp');
                } elseif ( $some_sites_has_queue_purge_active ) {
                    $feedback = __('Cache cleared for all sites, but note that some sites are using queue based cache purging and will be purged in a moment.', 'servebolt-wp');
                } else {
                    $feedback = __('Cache cleared for all sites', 'servebolt-wp');
                }

                wp_send_json_success( [
                    'type'   => 'success',
                    'markup' => $feedback,
                ] );
            }
        }
    }
    */

    /**
     * Handle the "item already in the queue".
     *
     * @param $purgeResult
     */
    /*
    private function handlePurgeItemAlreadyInQueue($purgeResult): void
    {
        switch ( $purgeResult->get_error_code() ) {
            case 'url_purge_item_already_in_queue':
                wp_send_json_success( [
                    'type'    => 'warning',
                    'title'   => __('Oh!', 'servebolt-wp'),
                    'message' => __('All good, the URL is already in the cache purge queue!', 'servebolt-wp'),
                ] );
                break;
            case 'post_purge_item_already_in_queue':
                wp_send_json_success( [
                    'type'    => 'warning',
                    'title'   => __('Oh!', 'servebolt-wp'),
                    'message' => __('All good, the post is already in the cache purge queue.', 'servebolt-wp'),
                ] );
                break;
            default:
                wp_send_json_error();
        }
    }
    */

    /**
     * Generate markup for user feedback after purging cache on all sites in multisite-network.
     *
     * @param $failed
     *
     * @return string
     */
    /*
    private function purgeNetworkCacheFailedSites($failed): string
    {
        $markup = '<strong>' . __('The cache was cleared on all sites except the following:', 'servebolt-wp') . '</strong>';
        $markup .= createLiTagsFromArray($failed, function ($item) {
            return getBlogName($item['blog_id']) . ( $item['reason'] ? ' (' . $item['reason'] . ')' : '' );
        });
        return $markup;
    }
    */
}