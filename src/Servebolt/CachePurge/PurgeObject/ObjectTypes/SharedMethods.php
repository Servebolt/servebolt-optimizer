<?php

namespace Servebolt\Optimizer\CachePurge\PurgeObject\ObjectTypes;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use WP_Query;

/**
 * Trait SharedMethods
 *
 * This trait contains common methods for building a cache purge object of various types (post, term etc.).
 */
abstract class SharedMethods
{

    /**
     * The ID of the object to be purged.
     *
     * @var
     */
    private $id;

    /**
     * The URLs related to the object about to be purged.
     *
     * @var array
     */
    private $urls = [];

    /**
     * The CacheTags related to the object about to be purged.
     *
     * @var array
     */
    private $tags = [];

    /**
     * Array of additional arguments for the object to be purged.
     *
     * @var array
     */
    private $args = [];

    /**
     * Whether we could resolve the purge object from the ID (post/term lookup).
     *
     * @var null
     */
    private $success = false;

    /**
     * SharedMethods constructor.
     *
     * @param $id
     * @param $args
     */
    protected function __construct($id, $args)
    {
        $this->setId($id);
        $this->setArguments($args);
        if ( $this->initObject() ) { // Check if we could find the object first
            // Check if we should generate all other related URLs for object
            if (apply_filters('sb_optimizer_should_generate_other_urls', true)) {
                $this->generateOtherUrls();
            }
        }
        $this->postUrlGenerateActions();        
    }

    /**
     * Do stuff after we have generated URLs.
     */
    private function postUrlGenerateActions(): void
    {
        // Let other manipulate URLs
        $this->setUrls(
            apply_filters(
                'sb_optimizer_alter_urls_for_cache_purge_object',
                $this->getUrls(),
                $this->getId(),
                $this->objectType
            )
        );
    }

     /**
     * Do stuff after we have generated CacheTags.
     */
    protected function postTagGenerateActions(): void
    {
        // Let other manipulate CacheTags
        $this->setCacheTags(
            apply_filters(
                'sb_optimizer_alter_tags_for_cache_purge_object',
                $this->getCacheTags(),
                $this->getId(),
                $this->objectType
            )
        );
    }
    /**
     * Set/get whether we could resolve the purge object from the ID (post/term lookup).
     *
     * @param null $bool
     * @return bool|void
     */
    public function success($bool = null): ?bool
    {
        if (is_bool($bool)) {
            $this->success = $bool;
            return null;
        }
        return $this->success === true;
    }

    /**
     * Set the ID of the object to be pured.
     *
     * @param $id
     */
    protected function setId($id): void
    {
        $this->id = $id;
    }

    /**
     * Set the arguments of the object to be pured.
     *
     * @param $args
     */
    protected function setArguments($args): void
    {
        $this->args = $args;
    }

    /**
     * Get and argument for the object to be purged.
     *
     * @param string $key
     * @return bool|mixed
     */
    protected function getArgument(string $key)
    {
        if (array_key_exists($key, $this->args)) {
            return $this->args[$key];
        }
        return false;
    }

    /**
     * Get the ID of the object to be pured.
     *
     * @return int|mixed
     */
    public function getId()
    {
        if (is_numeric($this->id)) {
            return (int) $this->id; // Make sure to return ID as int if it is numerical
        }
        return $this->id;
    }

    /**
     * Add URL to be purged cache for.
     *
     * @param string $url
     * @return bool
     */
    public function addUrl(string $url): bool
    {
        $urls = $this->getUrls();
        if (!in_array($url, $urls)) {
            $urls[] = $url;
            $this->setUrls($urls);
            return true;
        }
        return false;
    }

    /**
     * Add multiple URLs to be purged cache for.
     *
     * @param array $urls
     */
    public function addUrls(array $urls): void
    {
        if (!is_array($urls)) {
            return;
        }
        array_map(function ($url) {
            $this->addUrl($url);
        }, $urls);
    }

    /**
     * Set the URLs to purge cache for.
     *
     * @param array $urls
     * @return bool
     */
    public function setUrls(array $urls): bool
    {
        if (!is_array($urls)) {
            return false;
        }
        $this->urls = $urls;
        return true;
    }

    /**
     * Get the URLs to purge cache for.
     *
     * @return array
     */
    public function getUrls()
    {
        return $this->urls;
    }

    /**
     * Check if current post type is equals a given post type.
     *
     * @param string $postType
     * @return bool
     */
    protected function postTypeIs(string $postType): bool
    {
        return $postType == $this->getPostType();
    }

    /**
     * Check whether this post (attachment) is an image.
     *
     * @return bool
     */
    protected function attachmentIsImage(): bool
    {
        return wp_attachment_is_image($this->getId());
    }

    /**
     * Add the front page to be purged.
     */
    public function addFrontPage(): void
    {
        if ($frontPageId = get_option('page_on_front')) {
            if ($frontPageUrl = get_permalink($frontPageId)) {
                $this->addUrl($frontPageUrl);
            }
        }
    }

    /**
     * Find out how many pages needed for an archive.
     *
     * @param array $queryArgs
     * @param string $type
     * @return int
     */
    protected function getPagesNeeded(array $queryArgs, string $type): int
    {
        $queryArgs = wp_parse_args($queryArgs, [
            'post_type'   => 'any',
            'post_status' => 'publish',
        ]);
        $queryArgs = (array) apply_filters('sb_optimizer_cache_purge_pages_needed_post_query_arguments', $queryArgs, $type, $this->getId());
        $query = new WP_Query($queryArgs);
        if (!$query->have_posts()) {
            return (int) apply_filters('sb_optimizer_cache_purge_pages_needed_no_posts', 0, $type, $this->getId());
        }

        /**
         * Determine how many pages is needed for the current archive.
         *
         * @param int $maxNumPages Number of pages needed according to the WP Query.
         * @param string $type The object type, either "post" or "term".
         * @param id $id The ID of the object.
         */
        return (int) apply_filters('sb_optimizer_cache_purge_pages_needed', $query->max_num_pages, $type, $this->getId());
    }

    /**
     * Add URL to be purged cache for.
     *
     * @param string $url
     * @return bool
     */
    public function addCacheTag(string $tag): bool
    {
        $tags = $this->getCacheTags();
        if (!in_array($tag, $tags)) {
            $tags[] = $tag;
            $this->setCacheTags($tags);
            return true;
        }
        return false;
    }

    /**
     * Add multiple CacheTags to be purged cache for.
     *
     * @param array $tags
     */
    public function addCacheTags(array $tags): void
    {
        if (!is_array($tags)) {
            return;
        }
        array_map(function ($tag) {
            $this->addCacheTag($tag);
        }, $tags);
    }

    /**
     * Set the CacheTags to purge cache for.
     *
     * @param array $tags
     * @return bool
     */
    public function setCacheTags(array $tags): bool
    {
        if (!is_array($tags)) {
            return false;
        }
        $this->tags = $tags;
        return true;
    }

    /**
     * Get the CacheTags to purge cache for.
     *
     * @return array
     */
    public function getCacheTags()
    {
        return $this->tags;
    }

}
