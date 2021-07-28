<?php

namespace Servebolt\Optimizer\Admin\CachePurgeControl\Ajax;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Exception;
use Servebolt\Optimizer\CachePurge\WordPressCachePurge\WordPressCachePurge;
use Servebolt\Optimizer\CachePurge\CachePurge;
use Servebolt\Optimizer\Admin\SharedAjaxMethods;
use Servebolt\Optimizer\Exceptions\ApiError;
use Servebolt\Optimizer\Exceptions\ApiMessage;
use Servebolt\Optimizer\Exceptions\QueueError;
use Servebolt\Optimizer\Queue\Queues\WpObjectQueue;
use function Servebolt\Optimizer\Helpers\arrayGet;
use function Servebolt\Optimizer\Helpers\getPostTypeSingularName;
use function Servebolt\Optimizer\Helpers\getTaxonomyFromTermId;
use function Servebolt\Optimizer\Helpers\postExists;
use function Servebolt\Optimizer\Helpers\ajaxUserAllowedByFunction;
//use function Servebolt\Optimizer\Helpers\getBlogName;
//use function Servebolt\Optimizer\Helpers\createLiTagsFromArray;
use function Servebolt\Optimizer\Helpers\isSuperadmin;
//use function Servebolt\Optimizer\Helpers\countSites;
use function Servebolt\Optimizer\Helpers\iterateSites;

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
        add_action('wp_ajax_servebolt_purge_term_cache', [$this, 'purgeTermCacheCallback']);

        if ( is_multisite() ) {
            add_action('wp_ajax_servebolt_purge_network_cache', [$this, 'purgeNetworkCacheCallback']);
        }
    }

    /**
     * Ensure that the cache purge feature is active, unless send an error response.
     *
     * @param int|null $blogId
     */
    private function ensureCachePurgeFeatureIsActive(?int $blogId = null): void
    {
        if (!CachePurge::featureIsAvailable($blogId)) {
            wp_send_json_error([
                'message' => __('The cache purge feature is not active or is not configured correctly, so we could not purge cache.', 'servebolt-wp'),
            ]);
        }
    }

    /**
     * Check if we already have a purge all request in queue.
     *
     * @return bool
     */
    private function hasPurgeAllRequestInQueue(): bool
    {
        $queueInstance = WpObjectQueue::getInstance();
        return $queueInstance->hasPurgeAllRequestInQueue();
    }

    /**
     * Check if current user can purge all cache.
     *
     * @return bool
     */
    public static function canPurgeAllCache(): bool
    {
        return apply_filters(
            'sb_optimizer_can_purge_all_cache',
            current_user_can('edit_others_posts')
        );
    }

    /**
     * Purge all cache cache.
     */
    public function purgeAllCacheCallback()
    {
        $this->checkAjaxReferer();
        ajaxUserAllowedByFunction(__CLASS__ . '::canPurgeAllCache');
        $this->ensureCachePurgeFeatureIsActive();

        $queueBasedCachePurgeIsActive = CachePurge::queueBasedCachePurgeIsActive();

        if ($queueBasedCachePurgeIsActive && $this->hasPurgeAllRequestInQueue()) {
            wp_send_json_success([
                'title' => __('All good!', 'servebolt-wp'),
                'message' => __('A purge all-request is already queued and should be executed shortly.', 'servebolt-wp'),
            ]);
            return;
        }

        try {
            WordPressCachePurge::purgeAll();
            if ($queueBasedCachePurgeIsActive) {
                wp_send_json_success( [
                    'title' => __('Just a moment', 'servebolt-wp'),
                    'message' => __('A purge all-request was added to the queue and will be executed shortly.', 'servebolt-wp'),
                ] );
            } else {
                wp_send_json_success(['message' => __('All cache was purged.', 'servebolt-wp')]);
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
     * Check if URL is already in queue.
     *
     * @param string $url
     * @param bool $attemptToResolvePostId
     * @return bool
     */
    private function urlAlreadyInQueue(string $url, bool $attemptToResolvePostId = true): bool
    {
        if ($attemptToResolvePostId && $postId = WordPressCachePurge::attemptToResolvePostIdFromUrl($url)) {
            $queueInstance = WpObjectQueue::getInstance();
            return $queueInstance->hasPostInQueue($postId);
        } else {
            $queueInstance = WpObjectQueue::getInstance();
            return $queueInstance->hasUrlInQueue($url);
        }
    }

    /**
     * Check if current user can purge cache by URL.
     *
     * @return bool
     */
    public static function canPurgeCacheByUrl(): bool
    {
        return apply_filters(
            'sb_optimizer_can_purge_cache_by_url',
            current_user_can('edit_others_posts')
        );
    }

    /**
     * Purge specific URL cache.
     */
    public function purgeUrlCacheCallback()
    {
        $this->checkAjaxReferer();
        ajaxUserAllowedByFunction(__CLASS__ . '::canPurgeCacheByUrl');
        $this->ensureCachePurgeFeatureIsActive();

        $url = (string) arrayGet('url', $_POST);
        if (!$url || empty($url)) {
            wp_send_json_error(['message' => __('Please specify the URL you would like to purge cache for.', 'servebolt-wp')]);
            return;
        }

        $queueBasedCachePurgeIsActive = CachePurge::queueBasedCachePurgeIsActive();

        if ($queueBasedCachePurgeIsActive && $this->urlAlreadyInQueue($url)) {
            wp_send_json_success([
                'title' => __('All good!', 'servebolt-wp'),
                'message' => sprintf(__('A cache purge request for the URL "%s" is already added to the queue and should be executed shortly.'), $url)
            ]);
            return;
        }

        try {
            WordPressCachePurge::purgeByUrl($url);
            if ($queueBasedCachePurgeIsActive) {
                wp_send_json_success([
                    'title' => __('Just a moment', 'servebolt-wp'),
                    'message' => sprintf(__('A cache purge request for the URL "%s" was added to the queue and will be executed shortly.', 'servebolt-wp'), $url),
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
     * Check if post is already in queue.
     *
     * @param int $postId
     * @return bool
     */
    private function postAlreadyInQueue(int $postId): bool
    {
        $queueInstance = WpObjectQueue::getInstance();
        return $queueInstance->hasPostInQueue($postId);
    }

    /**
     * Check if current user can purge cache for given post.
     *
     * @param int $postId
     * @return bool
     */
    public static function canPurgePostCache(int $postId): bool
    {
        return apply_filters(
            'sb_optimizer_can_purge_post_cache',
            (
                current_user_can('edit_others_posts')
                || (
                    current_user_can('edit_published_posts')
                    && current_user_can('edit_post', $postId)
                )
            ),
            $postId
        );
    }

    /**
     * Purge cache for post.
     */
    public function purgePostCacheCallback() : void
    {
        $this->checkAjaxReferer();
        $this->ensureCachePurgeFeatureIsActive();

        $postId = intval(arrayGet('post_id', $_POST));

        if (!$postId || empty($postId)) {
            wp_send_json_error(['message' => __('Please specify the post you would like to purge cache for.', 'servebolt-wp')]);
            return;
        } elseif (!postExists($postId)) {
            wp_send_json_error(['message' => __('The specified post does not exist.', 'servebolt-wp')]);
            return;
        } elseif (!self::canPurgePostCache($postId)) {
            wp_send_json_error(['message' => __('You are not allowed to purge cache for this post.', 'servebolt-wp')]);
        }

        $queueBasedCachePurgeIsActive = CachePurge::queueBasedCachePurgeIsActive();

        if ($queueBasedCachePurgeIsActive && $this->postAlreadyInQueue($postId)) {
            wp_send_json_success([
                'title' => __('All good!', 'servebolt-wp'),
                'message' => sprintf(__('A cache purge request for the %s "%s" is already added to the queue and should be executed shortly.', 'servebolt-wp'), getPostTypeSingularName($postId), get_the_title($postId)),
            ]);
            return;
        }

        try {
            WordPressCachePurge::purgeByPost($postId);
            if ($queueBasedCachePurgeIsActive) {
                wp_send_json_success( [
                    'title'   => __('Just a moment', 'servebolt-wp'),
                    'message' => sprintf(__('A cache purge request for the %s "%s" was added to the queue and will be executed shortly.', 'servebolt-wp'), getPostTypeSingularName($postId), get_the_title($postId)),
                ] );
            } else {
                wp_send_json_success(['message' => sprintf(__('Cache was purged for the %s "%s".', 'servebolt-wp'), getPostTypeSingularName($postId), get_the_title($postId))]);
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
     * Check if term is already in queue.
     *
     * @param int $termId
     * @param string $taxonomySlug
     * @return bool
     */
    private function termAlreadyInQueue(int $termId, string $taxonomySlug): bool
    {
        $queueInstance = WpObjectQueue::getInstance();
        return $queueInstance->hasTermInQueue($termId, $taxonomySlug);
    }


    /**
     * Check if current user can purge cache for given term.
     *
     * @param int $termId
     * @param null|string|object $taxonomy
     * @return bool
     */
    public static function canPurgeTermCache(int $termId, ?object $taxonomy = null): bool
    {
        if (is_string($taxonomy)) {
            $taxonomyObject = get_taxonomy($taxonomy);
        } elseif (is_a('\\WP_Taxonomy', $taxonomy)) {
            $taxonomyObject = $taxonomy;
        } elseif (!$taxonomyObject = getTaxonomyFromTermId($termId)) {
            $taxonomyObject = false;
        }
        $canPurgeTermCache = $taxonomyObject && current_user_can($taxonomyObject->cap->manage_terms);
        return apply_filters(
            'sb_optimizer_can_purge_term_cache',
            $canPurgeTermCache,
            $termId,
            $taxonomyObject
        );
    }

    /**
     * Purge cache for term.
     */
    public function purgeTermCacheCallback() : void
    {
        $this->checkAjaxReferer();
        $this->ensureCachePurgeFeatureIsActive();

        $termId = intval(arrayGet('term_id', $_POST));

        if (!$termId || empty($termId)) {
            wp_send_json_error(['message' => __('Please specify the term you would like to purge cache for.', 'servebolt-wp')]);
            return;
        } elseif (!term_exists($termId)) {
            wp_send_json_error(['message' => __('The specified term does not exist.', 'servebolt-wp')]);
            return;
        } elseif (!self::canPurgeTermCache($termId)) {
            wp_send_json_error(['message' => __('You are not allowed to purge cache for this taxonomy.', 'servebolt-wp')]);
        }

        $term = get_term($termId);
        $queueBasedCachePurgeIsActive = CachePurge::queueBasedCachePurgeIsActive();

        if ($queueBasedCachePurgeIsActive && $this->termAlreadyInQueue($termId, $term->taxonomy)) {
            wp_send_json_success([
                'title' => 'All good!',
                'message' => sprintf(__('A cache purge request for the term "%s" is already added to the queue and should be executed shortly.', 'servebolt-wp'), $term->name),
            ]);
            return;
        }

        try {
            WordPressCachePurge::purgeByTerm($termId, $term->taxonomy);
            if ($queueBasedCachePurgeIsActive) {
                wp_send_json_success( [
                    'title'   => __('Just a moment', 'servebolt-wp'),
                    'message' => sprintf(__('A cache purge request for the term "%s" was added to the queue and will be executed shortly.', 'servebolt-wp'), $term->name),
                ] );
            } else {
                wp_send_json_success(['message' => sprintf(__('Cache was purged for the term "%s".', 'servebolt-wp'), $term->name)]);
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
     * Check if current user can purge all cache.
     *
     * @return bool
     */
    public static function canPurgeAllNetworkCache(): bool
    {
        return apply_filters(
            'sb_optimizer_can_purge_all_network_cache',
            isSuperadmin() && current_user_can('manage_options')
        );
    }

    /**
     * Purge all Cloudflare cache in all sites in multisite-network.
     */
    public function purgeNetworkCacheCallback() : void
    {
        $this->checkAjaxReferer();
        ajaxUserAllowedByFunction(__CLASS__ . '::canPurgeAllNetworkCache');

        $result = [];
        iterateSites(function($site) use (&$result) {
            if (!CachePurge::featureIsAvailable($site->blog_id)) {
                $result[] = [
                    'blog_id' => $site->blog_id,
                    'success' => false,
                    'message' => __('', 'servebolt-wp'),
                ];
                return;
            }
            if (WordPressCachePurge::purgeAll(false, $site->blog_id)) {
                // TODO: Check if queue is active
            } else {
                $result[] = [
                    'blog_id' => $site->blog_id,
                    'success' => false,
                    'message' => __('', 'servebolt-wp'),
                ];
            }
        }, true);

        wp_send_json_success();
    }
}
