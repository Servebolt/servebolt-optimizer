<?php

namespace Servebolt\Optimizer\Admin\Ajax\CachePurge;

use Servebolt\Optimizer\Admin\Ajax\SharedMethods;

class PurgeActions extends SharedMethods
{

    /**
     * CachePurgeActions constructor.
     */
    public function __construct()
    {
        add_action('wp_ajax_servebolt_purge_all_cache', [$this, 'purgeAllCacheCallback']);
        add_action('wp_ajax_servebolt_purge_url_cache', [$this, 'purgeUrlCacheCallback']);
        add_action('wp_ajax_servebolt_purge_post_cache', [$this, 'purgePostCacheCallback']);
        if ( is_multisite() ) {
            add_action('wp_ajax_servebolt_purge_network_cache', [$this, 'purgeNetworkCacheCallback']);
        }
    }

    /**
     * Purge all cache in Cloudflare cache.
     */
    public function purgeAllCacheCallback() : void
    {
        $this->checkAjaxReferer();

        sb_ajax_user_allowed();
        $cron_purge_is_active = sb_cf_cache()->cron_purge_is_active();
        if ( ! sb_cf_cache()->cf_cache_feature_available() ) {
            wp_send_json_error( [ 'message' => 'Cloudflare cache feature is not active so we could not purge cache. Make sure you have added Cloudflare API credentials and selected zone.' ] );
        } elseif ( $cron_purge_is_active && sb_cf_cache()->has_purge_all_request_in_queue() ) {
            wp_send_json_success([
                'title' => 'All good!',
                'message' => 'A purge all-request is already queued and should be executed shortly.',
            ]);
        } elseif ( sb_cf_cache()->purge_all() ) {
            wp_send_json_success( [
                'title'   => $cron_purge_is_active ? 'Just a moment' : false,
                'message' => $cron_purge_is_active ? 'A purge all-request was added to the queue and will be executed shortly.' : 'All cache was purged.',
            ] );
        } else {
            wp_send_json_error();
        }
    }

    /**
     * Purge specific URL in Cloudflare cache.
     */
    public function purgeUrlCacheCallback() : void
    {
        $this->checkAjaxReferer();
        sb_ajax_user_allowed();

        $url = (string) sb_array_get('url', $_POST);

        if ( ! sb_cf_cache()->cf_cache_feature_available() ) {
            wp_send_json_error( [ 'message' => sb__('Cloudflare cache feature is not active so we could not purge cache. Make sure you have added Cloudflare API credentials and selected zone.') ] );
            return;
        }

        if ( ! $url || empty($url) ) {
            wp_send_json_error( [ 'message' => sb__('Please specify the URL you would like to purge cache for.') ] );
            return;
        }

        $cron_purge_is_active = sb_cf_cache()->cron_purge_is_active();
        $purge_result = sb_cf_cache()->purge_by_url($url);

        if ( $purge_result === false ) {
            wp_send_json_error(); // Unspecified error
            return;
        } elseif ( is_wp_error($purge_result) ) {
            $this->handlePurgeItemAlreadyInQueue($purge_result);
            return;
        }

        // Success
        if ( $cron_purge_is_active ) {
            wp_send_json_success( [
                'title'   => 'Just a moment',
                'message' => sprintf(sb__('A cache purge-request for the URL "%s" was added to the queue and will be executed shortly.'), $url),
            ] );
        } else {
            wp_send_json_success( [ 'message' => sprintf(sb__('Cache was purged for URL "%s".'), $url) ] );
        }
    }

    /**
     * Purge specific post in Cloudflare cache.
     */
    public function purgePostCacheCallback() : void
    {
        $this->checkAjaxReferer();
        sb_ajax_user_allowed();
        $post_id = intval( sb_array_get('post_id', $_POST) );

        if ( ! sb_cf_cache()->cf_cache_feature_available() ) {
            wp_send_json_error( [ 'message' => 'Cloudflare cache feature is not active so we could not purge cache. Make sure you have added Cloudflare API credentials and selected zone.' ] );
            return;
        } elseif ( ! $post_id || empty($post_id) ) {
            wp_send_json_error( [ 'message' => 'Please specify the post you would like to purge cache for.' ] );
            return;
        } elseif ( ! sb_post_exists($post_id) ) {
            wp_send_json_error( [ 'message' => 'The specified post does not exist.' ] );
            return;
        }

        $cron_purge_is_active = sb_cf_cache()->cron_purge_is_active();
        $purge_result = sb_cf_cache()->purge_by_post($post_id);

        if ( $purge_result === false ) {
            wp_send_json_error(); // Unspecified error
            return;
        } elseif ( is_wp_error($purge_result) ) {
            $this->handlePurgeItemAlreadyInQueue($purge_result);
            return;
        }

        // Success
        if ( $cron_purge_is_active ) {
            wp_send_json_success( [
                'title'   => 'Just a moment',
                'message' => sprintf(sb__('A cache purge-request for the post "%s" was added to the queue and will be executed shortly.'), get_the_title($post_id)),
            ] );
        } else {
            wp_send_json_success( [ 'message' => sprintf(sb__('Cache was purged for the post post "%s".'), get_the_title($post_id) ) ] );
        }
    }

    /**
     * Purge all Cloudflare cache in all sites in multisite-network.
     */
    public function purgeNetworkCacheCallback() : void
    {
        $this->checkAjaxReferer();
        sb_require_superadmin();

        $failed_purge_attempts = [];
        $queue_based_cache_purge_sites = [];
        sb_iterate_sites(function($site) use (&$failed_purge_attempts, &$queue_based_cache_purge_sites) {

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
                    'reason'  => sb__('Cloudflare feature not available'),
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
        $all_sites_use_queue_based_cache_purge = $queue_based_cache_purge_sites_count == sb_count_sites();
        $some_sites_has_queue_purge_active = $queue_based_cache_purge_sites_count > 0;

        $failed_purge_attempt_count = count($failed_purge_attempts);
        $all_failed = $failed_purge_attempt_count == sb_count_sites();

        if ( $all_failed ) {
            wp_send_json_error( [
                'message' => sb__('Could not purge cache on any sites.'),
            ] );
        } else {
            if ( $failed_purge_attempt_count > 0 ) {
                wp_send_json_success([
                    'type'   => 'warning',
                    'title'  => sb__('Could not clear cache on all sites'),
                    'markup' => $this->purgeNetworkCacheFailedSites($failed_purge_attempts),
                ]);
            } else {

                if ( $all_sites_use_queue_based_cache_purge ) {
                    $feedback = sb__('Cache will be cleared for all sites in a moment.');
                } elseif ( $some_sites_has_queue_purge_active ) {
                    $feedback = sb__('Cache cleared for all sites, but note that some sites are using queue based cache purging and will be purged in a moment.');
                } else {
                    $feedback = sb__('Cache cleared for all sites');
                }

                wp_send_json_success( [
                    'type'   => 'success',
                    'markup' => $feedback,
                ] );
            }
        }
    }

    /**
     * Handle the "item already in the queue".
     *
     * @param $purgeResult
     */
    private function handlePurgeItemAlreadyInQueue($purgeResult)
    {
        switch ( $purgeResult->get_error_code() ) {
            case 'url_purge_item_already_in_queue':
                wp_send_json_success( [
                    'type'    => 'warning',
                    'title'   => sb__('Oh!'),
                    'message' => sb__('All good, the URL is already in the cache purge queue!'),
                ] );
                break;
            case 'post_purge_item_already_in_queue':
                wp_send_json_success( [
                    'type'    => 'warning',
                    'title'   => sb__('Oh!'),
                    'message' => sb__('All good, the post is already in the cache purge queue.'),
                ] );
                break;
            default:
                wp_send_json_error();
        }
    }

    /**
     * Generate markup for user feedback after purging cache on all sites in multisite-network.
     *
     * @param $failed
     *
     * @return string
     */
    private function purgeNetworkCacheFailedSites($failed)
    {
        $markup = '<strong>' . sb__('The cache was cleared on all sites except the following:') . '</strong>';
        $markup .= sb_create_li_tags_from_array($failed, function ($item) {
            return sb_get_blog_name($item['blog_id']) . ( $item['reason'] ? ' (' . $item['reason'] . ')' : '' );
        });
        return $markup;
    }
}
