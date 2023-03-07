<?php

namespace Servebolt\Optimizer\CachePurge\PurgeObject\ObjectTypes;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\CachePurge\PurgeObject\ObjectTypes\SharedMethods;
use Servebolt\Optimizer\CacheTags\GetCacheTagsHeadersForLocation;
use function Servebolt\Optimizer\Helpers\getAllImageSizesByImage;
use function Servebolt\Optimizer\Helpers\convertOriginalUrlToString;

/**
 * Class Post
 *
 * This is a cache purge object with the type of post.
 */
class Cachetag extends SharedMethods
{

    /**
     * Define the type of this object in WP context.
     *
     * @var string
     */
    protected $objectType = 'cachetag';

    /**
     * Post constructor.
     * @param $postId
     * @param $args
     */
    public function __construct($id, $args)
    {
        $this->setId($id);
        $this->setArguments($args);
        if ( $this->initObject() ) { // Check if we could find the object first
            // Check if we should generate all other related URLs for object
            if (apply_filters('sb_optimizer_should_generate_other_cache_tags', true)) {
                $this->generateOtherCacheTags();
            }
        }
        $this->postTagGenerateActions();        
    }

    /**
     * Get the post URL.
     *
     * @return mixed
     */
    public function getBaseUrl()
    {
        return get_permalink($this->getId());
    }

    /**
     * Get the post edit URL.
     *
     * @return null|string
     */
    public function getEditUrl(): ?string
    {
        return get_edit_post_link($this->getId());
    }

    /**
     * Get the post title.
     *
     * @return string
     */
    public function getTitle(): string
    {
        return get_the_title($this->getId());
    }

    /**
     * Add URLs related to a post object.
     *
     * @return bool
     */
    protected function initObject(): bool
    {
        // The URL to the post itself
        if ($this->addPostUrl()) {
            $this->success(true); // Flag that found the post
            return true;
        } else {
            return false; // Could not find the post, stop execution
        }
    }

    /**
     * Generate URLs related to the object.
     */
    protected function generateOtherCacheTags(): void
    {
        $cacheHeaders = new GetCacheTagsHeadersForLocation($this->getId(), $this->getPostType());
        // get related cache tags for this post.
        $this->addCacheTags($cacheHeaders->getHeaders());
        // make sure that the post page is cleared
        $this->addPostUrl();
        // add urls for images.
        $this->addAttachmentUrl();
        // sizes of iamges
        $this->addImageSizes();        
        // Allow for third parties to add URLs to be purged
        do_action('sb_optimizer_cache_purge_3rd_party_cache_tags', $this->getId(), $this);
        do_action('sb_optimizer_post_cache_purge_3rd_party_cache_tags', $this->getId(), $this);
        do_action('sb_optimizer_post_cache_purge_3rd_party_cache_tags_post_type_' . $this->getPostType(), $this->getId(), $this);
    }

    /**
     * Add the URL of the attachment.
     */
    private function addAttachmentUrl(): void
    {
        if ($attachmentUrl = wp_get_attachment_url($this->getId())) {
            $this->addUrl($attachmentUrl);
        }
    }

    /**
     * Add URLs to all sizes of image.
     */
    private function addImageSizes(): void
    {
        if ($sizes = getAllImageSizesByImage($this->getId())) {
            $this->addUrls($sizes);
        }
    }

    /**
     * Add the URL of a post to be purged.
     *
     * @return bool
     */
    private function addPostUrl(): bool
    {
        $postPermalink = $this->getBaseUrl();
        if ($postPermalink && !is_wp_error($postPermalink)) {
            $this->addUrl($postPermalink);
            $this->handleUrlCachePurgeStringDifference($postPermalink);
            return true;
        }
        return false;
    }

    /**
     * If this post purge originates from a URL, and that URL is defined differently than the one from
     * get_permalink, then we need to include the originating URL.
     *
     * @param string $postPermalink
     */
    private function handleUrlCachePurgeStringDifference(string $postPermalink): void
    {
        if (has_filter('sb_optimizer_purge_by_post_original_url')) {
            $originalUrl = apply_filters('sb_optimizer_purge_by_post_original_url', null);
            
            $originalUrl = convertOriginalUrlToString($originalUrl);         
            remove_all_filters('sb_optimizer_purge_by_post_original_url');
            if ($originalUrl && $postPermalink !== $originalUrl) {
                $this->addUrl($originalUrl);
            }
        }
    }

    /**
     * Get the post type of the post object.
     *
     * @return string|false
     */
    protected function getPostType()
    {
        return get_post_type($this->getId());
    }
}
