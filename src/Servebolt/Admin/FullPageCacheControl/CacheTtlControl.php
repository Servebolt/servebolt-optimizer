<?php

namespace Servebolt\Optimizer\Admin\FullPageCacheControl;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\FullPageCache\CacheTtl;
use Servebolt\Optimizer\Traits\Singleton;
use function Servebolt\Optimizer\Helpers\getOption;
use function Servebolt\Optimizer\Helpers\getOptionName;
use function Servebolt\Optimizer\Helpers\getVersionForStaticAsset;
use function Servebolt\Optimizer\Helpers\isScreen;
use function Servebolt\Optimizer\Helpers\view;

/**
 * Class CacheTtlControl
 * @package Servebolt\Optimizer\Admin\FullPageCacheControl
 */
class CacheTtlControl
{
    use Singleton;

    public static function init(): void
    {
        self::getInstance();
    }

    /**
     * CachePurge constructor.
     */
    public function __construct()
    {
        $this->initAssets();
        $this->initSettings();
        $this->rewriteHighlightedMenuItem();
    }

    /**
     * Flag "Cache"-page as active when on cache purge options page.
     */
    private function rewriteHighlightedMenuItem(): void
    {
        add_filter('parent_file', function($parentFile) {
            global $plugin_page;
            if ('servebolt-cache-ttl' === $plugin_page) {
                $plugin_page = 'servebolt-html-cache';
            }
            return $parentFile;
        });
        // Fix faulty page title
        add_filter('admin_title', function($admin_title, $title) {
            if (isScreen('admin_page_servebolt-cache-ttl')) {
                return __('Cache TTL', 'servebolt-wp') . ' ' . $admin_title;
            }
            return $admin_title;
        }, 10, 2);
    }

    /**
     * Render the options page.
     */
    public function render(): void
    {
        $settings = $this->getSettingsItemsWithValues();
        $cacheTtlOptions = CacheTtl::getTtlPresets();
        $postTypes = CacheTtl::getPostTypes();
        $taxonomies = CacheTtl::getTaxonomies();
        view(
            'cache-settings.cache-ttl.cache-ttl',
            compact([
                'settings',
                'postTypes',
                'taxonomies',
                'cacheTtlOptions',
            ])
        );
    }

    /**
     * The maximum number of queue items to display in the list.
     *
     * @return int
     */
    private function maxNumberOfCachePurgeQueueItems() : int
    {
        return (int) apply_filters('sb_optimizer_purge_item_list_limit', 500);
    }

    private function initAssets(): void
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueueScripts']);
    }

    public function enqueueScripts(): void
    {
        if (!isScreen('admin_page_servebolt-cache-ttl')) {
            return;
        }
        wp_enqueue_script(
            'servebolt-optimizer-cache-ttl-scripts',
            SERVEBOLT_PLUGIN_DIR_URL . 'assets/dist/js/cache-ttl.js',
            [],
            getVersionForStaticAsset(SERVEBOLT_PLUGIN_DIR_PATH . 'assets/dist/js/cache-ttl.js'),
            true
        );
    }

    private function initSettings(): void
    {
        add_action('admin_init', [$this, 'registerSettings']);
    }

    /**
     * Get all plugin settings in array.
     *
     * @return array
     */
    /**
     * Get all plugin settings in array.
     *
     * @return array
     */
    public function getSettingsItemsWithValues(): array
    {
        $items = $this->getSettingsItems();
        $itemsWithValues = [];
        foreach ($items as $item) {
            switch ($item) {
                default:
                    $itemsWithValues[$item] = getOption($item);
                    break;
            }
        }
        return $itemsWithValues;
    }

    public function registerSettings(): void
    {
        foreach ($this->getSettingsItems() as $key) {
            register_setting('sb-cache-ttl-options-page', getOptionName($key));
        }
    }

    /**
     * Settings items for CF cache.
     *
     * @return array
     */
    private function getSettingsItems(): array
    {
        return [
            'custom_cache_ttl_switch',
            'cache_ttl_by_post_type',
            'cache_ttl_by_taxonomy',
            'custom_cache_ttl_by_post_type',
            'custom_cache_ttl_by_taxonomy',
        ];
    }
}
