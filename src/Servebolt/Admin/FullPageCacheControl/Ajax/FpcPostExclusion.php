<?php

namespace Servebolt\Optimizer\Admin\FullPageCacheControl\Ajax;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Admin\SharedAjaxMethods;
use function Servebolt\Optimizer\Helpers\arrayGet;
use function Servebolt\Optimizer\Helpers\formatCommaStringToArray;
use function Servebolt\Optimizer\Helpers\ajaxUserAllowed;
use function Servebolt\Optimizer\Helpers\createLiTagsFromArray;
use function Servebolt\Optimizer\Helpers\fpcExcludePostTableRowMarkup;
use function Servebolt\Optimizer\Helpers\nginxFpc;

/**
 * Class FpcPostExclusion
 * @package Servebolt\Optimizer\Admin\FullPageCacheControl\Ajax
 */
class FpcPostExclusion extends SharedAjaxMethods
{

    /**
     * FpcPostExclusion constructor.
     */
    public function __construct()
    {
        add_action('wp_ajax_servebolt_update_fpc_exclude_posts_list', [$this, 'updateFpcExcludePostsListCallback']);
        add_action('wp_ajax_servebolt_fpc_exclude_post', [$this, 'updateExcludedPostsCallback']);
    }

    /**
     * Update FPC post exclude list.
     */
    public function updateFpcExcludePostsListCallback(): void
    {
        $this->checkAjaxReferer();
        ajaxUserAllowed();

        $itemsToRemove = arrayGet('items', $_POST);
        if ($itemsToRemove === 'all') {
            nginxFpc()->set_ids_to_exclude_from_cache([]);
            wp_send_json_success();
        }
        if (!$itemsToRemove || empty($itemsToRemove)) {
            wp_send_json_error();
        }
        if (!is_array($itemsToRemove)) {
            $itemsToRemove = [];
        }
        $itemsToRemove = array_filter($itemsToRemove, function ($item) {
            return is_numeric($item);
        });
        $currentItems = nginxFpc()->get_ids_to_exclude_from_cache();
        if (!is_array($currentItems)) {
            $currentItems = [];
        }
        $updatedItems = array_filter($currentItems, function($item) use ($itemsToRemove) {
            return ! in_array($item, $itemsToRemove);
        });
        nginxFpc()->set_ids_to_exclude_from_cache($updatedItems);
        wp_send_json_success();
    }

    /**
     * Add post IDs to be excluded from FPC.
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

        $onlyOnePostId = count($postIds) === 1;
        $invalid = [];
        $failed = [];
        $alreadyExcluded = [];
        $added = [];
        $success = [];
        $newMarkup = '';

        foreach ($postIds as $postId) {

            if ( ! is_numeric($postId) || ! $post = get_post($postId) ) {
                $invalid[] = $postId;
                continue;
            }

            if ( nginxFpc()->should_exclude_post_from_cache($postId) ) {
                $alreadyExcluded[] = $postId;
                $success[] = $postId;
                continue;
            }

            if (nginxFpc()->exclude_post_from_cache($postId) ) {
                $newMarkup .= fpcExcludePostTableRowMarkup($postId, false);
                $success[] = $postId;
                $added[] = $postId;
                continue;
            }

            $failed[] = $postId;
        }

        $gotSuccess = count($success);
        $hasInvalid = count($invalid) > 0;
        $hasFailed  = count($failed) > 0;
        $hasInvalidOnly = ! $gotSuccess && $hasInvalid && ! $hasFailed;
        $hasFailedOnly = ! $gotSuccess && $hasFailed && ! $hasInvalid;

        $type = 'success';
        $title = __('All good', 'servebolt-wp');
        $returnMethod = 'wp_send_json_success';

        $invalidMessage = '';
        $failedMessage = '';
        $alreadyExcludedMessage = '';
        $addedMessage = '';

        if ( $hasInvalid ) {
            $type = 'warning';
            $title = __('We made progress, but...', 'servebolt-wp');
            $invalidMessage = sprintf(__('The following %s were invalid:', 'servebolt-wp'), ( $onlyOnePostId ? __('post ID', 'servebolt-wp') : __('post IDs', 'servebolt-wp') ) ) . createLiTagsFromArray($invalid);
        }

        if ( $hasFailed ) {
            $type = 'warning';
            $title = __('We made progress, but...', 'servebolt-wp');
            $failedMessage = __('Could not exclude the following posts from cache:', 'servebolt-wp') . createLiTagsFromArray($failed, function($postId) {
                $title = get_the_title($postId);
                return $title ? $title . ' (ID ' . $postId . ')' : $postId;
            });
        }

        if ( count($alreadyExcluded) > 0 ) {
            $alreadyExcludedMessage = __('The following posts are already excluded from cache:', 'servebolt-wp') . createLiTagsFromArray($alreadyExcluded, function($postId) {
                $title = get_the_title($postId);
                return $title ? $title . ' (ID ' . $postId . ')' : $postId;
            });
        }

        if ( count($added) > 0 ) {
            $addedMessage = __('The following posts were excluded from cache:', 'servebolt-wp') . createLiTagsFromArray($added, function($postId) {
                $title = get_the_title($postId);
                return $title ? $title . ' (ID ' . $postId . ')' : $postId;
            });
        }

        if ( $hasInvalidOnly ) {
            $returnMethod = 'wp_send_json_error';
            $type = 'warning';
            $title = ( $onlyOnePostId ? __('Post ID invalid', 'servebolt-wp') : __('Post IDs invalid', 'servebolt-wp') );
        } elseif ( $hasFailedOnly ) {
            $returnMethod = 'wp_send_json_error';
            $title = __('Could not update exclude list', 'servebolt-wp');
            $type = 'error';

        } elseif (!$gotSuccess) {
            $returnMethod = 'wp_send_json_error';
            $title = __('Something went wrong', 'servebolt-wp');
            $type = 'warning';
        }

        $message = $invalidMessage . $failedMessage . $alreadyExcludedMessage . $addedMessage;

        $returnMethod([
            'type'       => $type,
            'title'      => $title,
            'message'    => $message,
            'row_markup' => $newMarkup,
        ]);
    }
}
