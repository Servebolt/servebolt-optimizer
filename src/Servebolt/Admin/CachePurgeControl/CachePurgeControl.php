<?php

namespace Servebolt\Optimizer\Admin\CachePurgeControl;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Api\Cloudflare\Cloudflare;
use Servebolt\Optimizer\CachePurge\CachePurge;
use Servebolt\Optimizer\Admin\CachePurgeControl\Ajax\Configuration;
use Servebolt\Optimizer\Admin\CachePurgeControl\Ajax\PurgeActions;
//use Servebolt\Optimizer\Admin\CachePurgeControl\Ajax\QueueHandling;
use Servebolt\Optimizer\Traits\Singleton;
use function Servebolt\Optimizer\Helpers\getVersionForStaticAsset;
use function Servebolt\Optimizer\Helpers\isScreen;
use function Servebolt\Optimizer\Helpers\view;
use function Servebolt\Optimizer\Helpers\isHostedAtServebolt;
use function Servebolt\Optimizer\Helpers\getOptionName;
use function Servebolt\Optimizer\Helpers\getOption;

/**
 * Class CachePurgeControl
 * @package Servebolt\Optimizer\Admin\CachePurgeControl
 */
class CachePurgeControl
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
        $this->initAjax();
        $this->initAssets();
        $this->initSettings();
        CachePurgeRowActions::init();
        if (isHostedAtServebolt()) {
            $this->rewriteHighlightedMenuItem();
        }
    }

    /**
     * Flag "Cache"-page as active when on cache purge options page.
     */
    private function rewriteHighlightedMenuItem(): void
    {
        add_filter('parent_file', function($parentFile) {
            global $plugin_page;
            if ('servebolt-cache-purge-control' === $plugin_page) {
                $plugin_page = 'servebolt-html-cache';
            }
            return $parentFile;
        });
        // Fix faulty page title
        add_filter('admin_title', function($admin_title, $title) {
            if (isScreen('admin_page_servebolt-cache-purge-control')) {
                return __('Cache purging', 'servebolt-wp') . ' ' . $admin_title;
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
        $cachePurge = $this;
        $isHostedAtServebolt = isHostedAtServebolt();

        $selectedCfZone = $this->getSelectedCfZone($settings);
        $cfZones = $this->getCfZones($settings);
        $cachePurgeIsActive = CachePurge::isActive();
        $automaticCachePurgeOnContentUpdateIsActive = CachePurge::automaticCachePurgeOnContentUpdateIsActive();
        $automaticCachePurgeOnSlugChangeIsActive = CachePurge::automaticCachePurgeOnSlugChangeIsActive();
        $automaticCachePurgeOnDeletionIsActive = CachePurge::automaticCachePurgeOnDeletionIsActive();
        $automaticCachePurgeOnAttachmentUpdateIsActive = CachePurge::automaticCachePurgeOnAttachmentUpdateIsActive();
        $acdLock = CachePurge::cachePurgeIsLockedTo('acd');
        $queueBasedCachePurgeActiveStateIsOverridden = CachePurge::queueBasedCachePurgeActiveStateIsOverridden();
        $queueBasedCachePurgeIsActive = CachePurge::queueBasedCachePurgeIsActive();
        $automaticCachePurgeIsAvailable = CachePurge::automaticCachePurgeIsAvailable();

        view(
            'cache-settings.cache-purge.cache-purge',
            compact([
                'settings',
                'cachePurge',
                'isHostedAtServebolt',
                'selectedCfZone',
                'cfZones',
                'automaticCachePurgeIsAvailable',
                'cachePurgeIsActive',
                'automaticCachePurgeOnContentUpdateIsActive',
                'automaticCachePurgeOnSlugChangeIsActive',
                'automaticCachePurgeOnDeletionIsActive',
                'automaticCachePurgeOnAttachmentUpdateIsActive',
                'queueBasedCachePurgeActiveStateIsOverridden',
                'queueBasedCachePurgeIsActive',
                'acdLock',
            ])
        );
    }

    /**
     * Get a hashed string of CF credentials to be used as transient keys.
     *
     * @param $settings
     * @return string|null
     */
    private function getCfCredentialsHash($settings): ?string
    {
        switch ($settings['cf_auth_type']) {
            case 'api_token':
                if (!empty($settings['cf_api_token'])) {
                    return hash('SHA512', $settings['cf_api_token']);
                }
                break;
            case 'api_key':
                if (!empty($settings['cf_email']) && !empty($settings['cf_api_key'])) {
                    return hash('SHA512', $settings['cf_email']) . '_' . hash('SHA512', $settings['cf_api_key']);
                }
                break;
        }
        return null;
    }

    /**
     * Get available CF zones.
     *
     * @param $settings
     * @return array
     */
    private function getCfZones($settings): array
    {
        if ($credentialsHash = $this->getCfCredentialsHash($settings)) {
            $listZonesTransientKey = 'sb_cf_list_zones_' . $credentialsHash;
            $zones = get_transient($listZonesTransientKey) ?? [];
            if (empty($zones)) {
                $cfApi = Cloudflare::getInstance();
                $zones = $cfApi->listZones(['name', 'id']);
                if (is_array($zones)) {
                    set_transient($listZonesTransientKey, $zones, 60);
                }
            }
            if (is_array($zones)) {
                return $zones;
            }
        }
        return [];
    }

    /**
     * Get the selected CF zone object.
     *
     * @param $settings
     * @return object|null
     */
    private function getSelectedCfZone($settings): ?object
    {
        if ($settings['cf_zone_id'] && $credentialsHash = $this->getCfCredentialsHash($settings)) {
            $zoneTransientKey = 'sb_cf_current_zone_' . $credentialsHash . '_' . $settings['cf_zone_id'];
            $selectedZone = get_transient($zoneTransientKey);
            if (!$selectedZone) {
                $cfApi = Cloudflare::getInstance();
                $selectedZone = $cfApi->getZoneById($settings['cf_zone_id']);
                if (is_object($selectedZone)) {
                    set_transient($zoneTransientKey, $selectedZone, DAY_IN_SECONDS);
                }
            }
            return $selectedZone;
        }
        return null;
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

    private function initAjax(): void
    {
        new Configuration;
        new PurgeActions;
        //new QueueHandling;
    }

    private function initAssets(): void
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueueScripts']);
    }

    public function enqueueScripts(): void
    {
        if (!isScreen('admin_page_servebolt-cache-purge-control')) {
            return;
        }
        wp_enqueue_script(
            'servebolt-optimizer-cache-purge-scripts',
            SERVEBOLT_PLUGIN_DIR_URL . 'assets/dist/js/cache-purge.js',
            ['servebolt-optimizer-scripts'],
            getVersionForStaticAsset(SERVEBOLT_PLUGIN_DIR_PATH . 'assets/dist/js/cache-purge.js'),
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
    public function getSettingsItemsWithValues(): array
    {
        $items = $this->getSettingsItems();
        $itemsWithValues = [];
        foreach ($items as $item) {
            switch ($item) {
                case 'cache_purge_driver':
                    if (!isHostedAtServebolt()) {
                        $value = $this->getDefaultCachePurgeDriver(); // Only allow Cloudflare when not hosted at Servebolt
                    } else {
                        //$value = getOption($item);
                        $value = CachePurge::getSelectedCachePurgeDriver();
                    }
                    $itemsWithValues['cache_purge_driver'] = $value ?: $this->getDefaultCachePurgeDriver();
                    break;
                case 'cf_auth_type':
                    $value = getOption($item);
                    $itemsWithValues['cf_auth_type'] = $value ?: $this->getDefaultCfAuthType();
                    break;
                default:
                    $itemsWithValues[$item] = getOption($item);
                    break;
            }
        }
        return $itemsWithValues;
    }

    /**
     * @return string
     */
    private function getDefaultCfAuthType(): string
    {
        return 'api_token';
    }

    /**
     * @return string
     */
    private function getDefaultCachePurgeDriver(): string
    {
        return 'cloudflare';
    }

    public function registerSettings(): void
    {
        foreach ($this->getSettingsItems() as $key) {
            register_setting('sb-cache-purge-options-page', getOptionName($key));
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
            'cache_purge_switch',
            'cache_purge_auto',
            'cache_purge_auto_on_slug_change',
            'cache_purge_auto_on_deletion',
            'cache_purge_auto_on_attachment_update',
            'cache_purge_driver',
            'cf_zone_id',
            'cf_auth_type',
            'cf_email',
            'cf_api_key',
            'cf_api_token',
            'queue_based_cache_purge',
        ];
    }

    /**
     * Get post Id if present.
     *
     * @return int|null
     */
    public static function getSinglePostId(): ?int
    {
        if (!is_admin() && is_singular() && $postId = get_the_ID()) {
            return $postId;
        }
        global $post, $pagenow;
        if (is_admin() && $pagenow == 'post.php' && $post->ID) {
            return $post->ID;
        }
        return null;
    }

    /**
     * Get term Id if present.
     *
     * @return int|null
     */
    public static function getSingleTermId(): ?int
    {
        if (!is_admin()) {
            $queriedObject = get_queried_object();
            if (is_a($queriedObject, 'WP_Term')) {
                return $queriedObject->term_id;
            }
        }
        global $pagenow;
        if (is_admin() && $pagenow == 'term.php') {
            if ($termId = absint($_REQUEST['tag_ID'])) {
                return $termId;
            }
        }
        return null;
    }
}
