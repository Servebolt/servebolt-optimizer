<?php

namespace Servebolt\Optimizer\Admin\FullPageCacheControl\Ajax;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Admin\SharedAjaxMethods;
use Servebolt\Optimizer\FullPageCache\CachePostExclusion;
use function Servebolt\Optimizer\Helpers\arrayGet;
use function Servebolt\Optimizer\Helpers\formatCommaStringToArray;
use function Servebolt\Optimizer\Helpers\ajaxUserAllowed;
use function Servebolt\Optimizer\Helpers\createLiTagsFromArray;
use function Servebolt\Optimizer\Helpers\htmlCacheExcludePostTableRowMarkup;
use function Servebolt\Optimizer\Helpers\postExists;

/**
 * Class HtmlCachePostExclusion
 * @package Servebolt\Optimizer\Admin\FullPageCacheControl\Ajax
 */
class HtmlCachePostExclusion extends SharedAjaxMethods
{

    /**
     * HtmlCachePostExclusion constructor.
     */
    public function __construct()
    {
        add_action('wp_ajax_servebolt_update_html_cache_exclude_posts_list', [$this, 'updateHtmlCacheExcludePostsListCallback']);
        add_action('wp_ajax_servebolt_html_cache_exclude_post', [$this, 'updateExcludedPostsCallback']);
    }

    /**
     * Update HTML Cache post exclude list - AJAX callback.
     */
    public function updateHtmlCacheExcludePostsListCallback(): void
    {
        $this->checkAjaxReferer();
        ajaxUserAllowed();
        $itemsToRemove = arrayGet('items', $_POST);
        if ($this->removeItemsFromHtmlCacheExclusion($itemsToRemove)) {
            wp_send_json_success();
        } else {
            wp_send_json_error();
        }
    }

    /**
     * Update HTML Cache post exclude list.
     *
     * @param $itemsToRemove
     * @return bool
     */
    public function removeItemsFromHtmlCacheExclusion($itemsToRemove): bool
    {
        if ($itemsToRemove === 'all') {
            CachePostExclusion::setIdsToExcludeFromCache([]);
            return true;
        }
        if (!$itemsToRemove || empty($itemsToRemove)) {
            return false;
        }
        if (!is_array($itemsToRemove)) {
            $itemsToRemove = [];
        }
        $itemsToRemove = array_filter($itemsToRemove, function ($item) {
            return is_numeric($item);
        });
        foreach ($itemsToRemove as $itemToRemove) {
            if (postExists($itemToRemove)) {
                do_action('sb_optimizer_post_removed_from_html_cache_exclusion', $itemToRemove);
            }
        }
        $currentItems = CachePostExclusion::getIdsToExcludeFromCache();
        if (!is_array($currentItems)) {
            $currentItems = [];
        }
        $updatedItems = array_filter($currentItems, function($item) use ($itemsToRemove) {
            return !in_array($item, $itemsToRemove);
        });
        CachePostExclusion::setIdsToExcludeFromCache($updatedItems);
        return true;
    }

    /**
     * Add post IDs to be excluded from HTML Cache - AJAX callback.
     */
    public function updateExcludedPostsCallback(): void
    {
        $this->checkAjaxReferer();
        ajaxUserAllowed();

        $postIdsString = arrayGet('post_ids', $_POST);
        $postIds = formatCommaStringToArray($postIdsString);

        if (!$postIds || empty($postIds)) {
            wp_send_json_error([
                'message' => __('Post IDs missing', 'servebolt-wp'),
            ]);
        }
        $result = $this->addItemsToHtmlCacheExclusion($postIds);
        $success = arrayGet('success', $result);
        unset($result['success']);
        if ($success) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * Add post IDs to be excluded from HTML Cache.
     *
     * @param $postIds
     * @return array
     */
    public function addItemsToHtmlCacheExclusion($postIds): array
    {
        $onlyOnePostId = count($postIds) === 1;
        $invalid = [];
        $failed = [];
        $alreadyExcluded = [];
        $added = [];
        $success = [];
        $newMarkup = '';

        foreach ($postIds as $postId) {

            if (!is_numeric($postId) || !$post = get_post($postId)) {
                $invalid[] = $postId;
                continue;
            }

            if (CachePostExclusion::shouldExcludePostFromCache($postId)) {
                $alreadyExcluded[] = $postId;
                $success[] = $postId;
                continue;
            }

            if (CachePostExclusion::excludePostFromCache($postId)) {
                do_action('sb_optimizer_post_added_to_html_cache_exclusion', $postId);
                $newMarkup .= htmlCacheExcludePostTableRowMarkup($postId, false);
                $success[] = $postId;
                $added[] = $postId;
                continue;
            }

            $failed[] = $postId;
        }

        $gotSuccess = count($success);
        $hasInvalid = count($invalid) > 0;
        $hasFailed  = count($failed) > 0;
        $hasInvalidOnly = !$gotSuccess && $hasInvalid && !$hasFailed;
        $hasFailedOnly = !$gotSuccess && $hasFailed && !$hasInvalid;

        $type = 'success';
        $title = __('All good', 'servebolt-wp');
        $success = true;

        $invalidMessage = '';
        $failedMessage = '';
        $alreadyExcludedMessage = '';
        $addedMessage = '';

        if ($hasInvalid) {
            $type = 'warning';
            $title = __('We made progress, but...', 'servebolt-wp');
            $invalidMessage = sprintf(__('The following %s were invalid:', 'servebolt-wp'), ( $onlyOnePostId ? __('post ID', 'servebolt-wp') : __('post IDs', 'servebolt-wp') ) ) . createLiTagsFromArray($invalid);
        }

        if ($hasFailed) {
            $type = 'warning';
            $title = __('We made progress, but...', 'servebolt-wp');
            $failedMessage = __('Could not exclude the following posts from cache:', 'servebolt-wp') . createLiTagsFromArray($failed, function($postId) {
                $title = get_the_title($postId);
                return $title ? $title . ' (ID ' . $postId . ')' : $postId;
            });
        }

        if (count($alreadyExcluded) > 0) {
            $alreadyExcludedMessage = __('The following posts are already excluded from cache:', 'servebolt-wp') . createLiTagsFromArray($alreadyExcluded, function($postId) {
                $title = get_the_title($postId);
                return $title ? $title . ' (ID ' . $postId . ')' : $postId;
            });
        }

        if (count($added) > 0) {
            $addedMessage = __('The following posts were excluded from cache:', 'servebolt-wp') . createLiTagsFromArray($added, function($postId) {
                $title = get_the_title($postId);
                return $title ? $title . ' (ID ' . $postId . ')' : $postId;
            });
        }

        if ($hasInvalidOnly) {
            $success = false;
            $type = 'warning';
            $title = ( $onlyOnePostId ? __('Post ID invalid', 'servebolt-wp') : __('Post IDs invalid', 'servebolt-wp') );
        } elseif ($hasFailedOnly) {
            $success = false;
            $title = __('Could not update exclude list', 'servebolt-wp');
            $type = 'error';

        } elseif (!$gotSuccess) {
            $success = false;
            $title = __('Something went wrong', 'servebolt-wp');
            $type = 'warning';
        }

        $message = $invalidMessage . $failedMessage . $alreadyExcludedMessage . $addedMessage;

        return [
            'success' => $success,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'row_markup' => $newMarkup,
        ];
    }
}
