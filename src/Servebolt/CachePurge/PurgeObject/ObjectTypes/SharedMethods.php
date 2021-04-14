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
            if (apply_filters('sb_optimizer_should_generate_other_urls', true)) { // Check if we should generate all other related URLs for object
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
        $queryArgs = (array) apply_filters('sb_optimizer_cf_cache_purge_pages_needed_post_query_arguments', $queryArgs, $type, $this->getId());
        $query = new WP_Query($queryArgs);
        if (!$query->have_posts()) {
            return (int) apply_filters('sb_optimizer_cf_cache_purge_pages_needed_no_posts', 0, $type, $this->getId());
        }
        return (int) apply_filters('sb_optimizer_cf_cache_purge_pages_needed', $query->max_num_pages, $type, $this->getId());
    }
}
