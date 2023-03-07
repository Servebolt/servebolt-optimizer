<?php

namespace Servebolt\Optimizer\CachePurge\PurgeObject\ObjectTypes;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use function Servebolt\Optimizer\Helpers\getAllImageSizesByImage;
use function Servebolt\Optimizer\Helpers\paginateLinksAsArray;
use function Servebolt\Optimizer\Helpers\convertOriginalUrlToString;

/**
 * Class Post
 *
 * This is a cache purge object with the type of post.
 */
class Post extends SharedMethods
{

    /**
     * Define the type of this object in WP context.
     *
     * @var string
     */
    protected $objectType = 'post';

    /**
     * Post constructor.
     * @param $postId
     * @param $args
     */
    public function __construct($postId, $args)
    {
        parent::__construct($postId, $args);
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
    protected function generateOtherUrls(): void
    {

        // The URL to the front page
        $this->addFrontPage();

        // The URL to the post type archive for the post type of the post
        $this->addPostTypeArchive();

        // The URL to the author archive
        $this->addAuthorArchive();

        // The URLs for categories, tags, post formats + any custom taxonomies for post type
        $this->addTaxonomyArchives();

        // Check if should care about date archive URLs when purging cache
        if ($this->dateArchiveActive()) {
            if ($this->postTypeIs('post')) {
                $this->addDateArchive();
            }
        }

        // Only for attachments
        if ($this->postTypeIs('attachment')) {
            $this->addAttachmentUrl();
            if ($this->attachmentIsImage()) {
                $this->addImageSizes();
            }
        }

        // Allow for third parties to add URLs to be purged
        do_action('sb_optimizer_cache_purge_3rd_party_urls', $this->getId(), $this);
        do_action('sb_optimizer_post_cache_purge_3rd_party_urls', $this->getId(), $this);
        do_action('sb_optimizer_post_cache_purge_3rd_party_urls_post_type_' . $this->getPostType(), $this->getId(), $this);
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
     * Whether we should care about date archive URLs when purging cache.
     *
     * @return bool
     */
    private function dateArchiveActive(): bool
    {
        return (bool) apply_filters('sb_optimizer_cache_purge_date_archive_active', false);
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
     * If this post purge originates from a URL, and that URL is defined differently than the one from get_permalink, then we need to include the originating URL.
     *
     * @param string $postPermalink
     */
    private function handleUrlCachePurgeStringDifference(string $postPermalink): void
    {
        if (has_filter('sb_optimizer_purge_by_post_original_url')) {
            $originalUrl = apply_filters('sb_optimizer_purge_by_post_original_url', null);            
            $originalUrl = convertOriginalUrlToString($originalUrl);
            remove_all_filters('sb_optimizer_purge_by_post_original_url');
            if (isset($originalUrl) && $postPermalink !== $originalUrl) {
                $this->addUrl($originalUrl);
            }
        }
    }

    /**
     * Check whether a certain post type should be purge cache for.
     *
     * @param string $postType
     * @return bool
     */
    private function postTypeArchiveShouldBePurged(string $postType): bool
    {
        return (bool) apply_filters('sb_optimizer_cache_purge_post_type_should_be_purged', true, $postType);
    }

    /**
     * Add post type archive to be purged.
     */
    private function addPostTypeArchive(): void
    {
        if (!$this->postTypeArchiveShouldBePurged($this->getPostType())) {
            return;
        }
        $postTypeArchiveUrl = get_post_type_archive_link($this->getPostType());
        if ($postTypeArchiveUrl && !is_wp_error($postTypeArchiveUrl)) {
            $pagesNeeded = $this->getPagesNeeded([
                'post_type' => $this->getPostType(),
            ], 'post');
            $this->addUrls(paginateLinksAsArray($postTypeArchiveUrl, $pagesNeeded));
        }
    }

    /**
     * Add author URL to be purged.
     */
    private function addAuthorArchive(): void
    {
        $author = $this->getPostAuthor();
        if ($author && !is_wp_error($author)) {
            $authorUrl = get_author_posts_url($author);
            if ($authorUrl && !is_wp_error($authorUrl)) {
                $pagesNeeded = $this->getPagesNeeded([
                    'post_type' => apply_filters('sb_optimizer_cache_purge_author_archive_post_type', 'post'),
                    'author'    => $author,
                ], 'post');
                $this->addUrls(paginateLinksAsArray($authorUrl, $pagesNeeded));
            }
        }
    }

    /**
     * Check whether a certain taxonomy should be purge cache for.
     *
     * @param $taxonomy
     * @return bool
     */
    private function taxonomyArchiveShouldBePurged($taxonomy): bool
    {
        return (bool) apply_filters('sb_optimizer_cache_purge_taxonomy_should_be_purged', true, $taxonomy);
    }

    /**
     * Add taxonomy terms URLs (where the post is present) to be purged.
     */
    private function addTaxonomyArchives(): void
    {
        $taxonomies = get_taxonomies([], 'objects');
        if (is_array($taxonomies)) {
            foreach ($taxonomies as $taxonomySlug => $taxonomy) {
                if (
                    ! $this->taxonomyArchiveShouldBePurged($taxonomy)
                    || ! in_array($this->getPostType(), $taxonomy->object_type)
                ) {
                    continue;
                }
                $termsForPostInTaxonomy = wp_get_post_terms($this->getId(), $taxonomySlug);
                if (is_array($termsForPostInTaxonomy)) {
                    foreach ($termsForPostInTaxonomy as $term) {
                        $termLink = get_term_link($term, $taxonomySlug);
                        if ($termLink && !is_wp_error($termLink)) {
                            $pagesNeeded = $this->getPagesNeeded([
                                'post_type' => $taxonomy->object_type,
                                'tax_query' => [
                                    [
                                        'taxonomy' => $taxonomySlug,
                                        'field' => 'slug',
                                        'terms' => $term->slug,
                                    ]
                                ],
                            ], 'post');
                            $this->addUrls(paginateLinksAsArray($termLink, $pagesNeeded));
                        }
                    }
                }
            }
        }
    }

    /**
     * Add date archive URLs to be purged.
     */
    private function addDateArchive(): void
    {
        $year  = get_the_time('Y', $this->getId());
        $month = get_the_time('m', $this->getId());
        $day   = get_the_time('d', $this->getId());
        $dateArchive = get_day_link($year, $month, $day);
        if ($dateArchive && !is_wp_error($dateArchive)) {
            $pagesNeeded = $this->getPagesNeeded([
                'post_type'  => apply_filters('sb_optimizer_cache_purge_date_archive_post_type', 'post'),
                'date_query' => [
                    compact('year', 'month', 'day')
                ]
            ], 'post');
            $this->addUrls(paginateLinksAsArray($dateArchive, $pagesNeeded));
        }
    }

    /**
     * Get the author of a post.
     *
     * @return mixed
     */
    private function getPostAuthor(): ?string
    {
        $post = get_post($this->getId());
        if (isset($post->post_author)) {
            return $post->post_author;
        }
        return null;
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
