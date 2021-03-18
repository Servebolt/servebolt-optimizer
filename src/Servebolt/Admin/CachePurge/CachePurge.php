<?php

namespace Servebolt\Optimizer\Admin\CachePurge;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use Servebolt\Optimizer\Api\Cloudflare\Cloudflare;
use Servebolt\Optimizer\Admin\AdminGuiController;
use Servebolt\Optimizer\Admin\Ajax\CachePurge\Configuration;
use Servebolt\Optimizer\Admin\Ajax\CachePurge\PurgeActions;
use Servebolt\Optimizer\Admin\Ajax\CachePurge\QueueHandling;
use Servebolt\Optimizer\Traits\Singleton;
use function Servebolt\Optimizer\Helpers\view;

class CachePurge
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
    }

    /**
     * Get available CF zones.
     *
     * @param $settings
     * @return array
     */
    private function getCfZones($settings): array
    {
        $listZonesBaseTransientKey = 'sb_cf_list_zones_';
        switch ($settings['cf_auth_type']) {
            case 'api_token':
                if (!empty($settings['cf_api_token'])) {
                    $listZonesTransientKey = $listZonesBaseTransientKey . hash('SHA512', $settings['cf_api_token']);
                }
                break;
            case 'api_key':
                if (!empty($settings['cf_email']) && !empty($settings['cf_api_key'])) {
                    $listZonesTransientKey = $listZonesBaseTransientKey . $settings['cf_email'] . '_' . hash('SHA512', $settings['cf_api_key']);
                }
                break;
        }
        if (isset($listZonesTransientKey)) {
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
     * Get the selected zone object.
     *
     * @param $settings
     * @return object|null
     */
    private function getSelectedCfZone($settings): ?object
    {
        if ($settings['cf_zone_id']) {
            $zoneTransientKey = 'sb_cf_current_zone_' . $settings['cf_zone_id'];
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
     * Render the options page.
     */
    public function render(): void
    {
        $adminGuiController = AdminGuiController::getInstance();
        $settings = $adminGuiController->getSettingsItemsWithValues();
        $cachePurge = $this;
        $isHostedAtServebolt = host_is_servebolt();

        $selectedCfZone = $this->getSelectedCfZone($settings);
        $cfZones = $this->getCfZones($settings);

        view('cache-purge/cache-purge', compact('settings', 'cachePurge', 'isHostedAtServebolt', 'selectedCfZone', 'cfZones'));
        /*
        $maxNumberOfCachePurgeQueueItems = $this->maxNumberOfCachePurgeQueueItems();
        $numberOfCachePurgeQueueItems = sb_cf_cache()->count_items_to_purge();
        Helpers\view('cache-purge/cache-purge', compact(
            'maxNumberOfCachePurgeQueueItems',
            'numberOfCachePurgeQueueItems'
        ));
        */
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
        new QueueHandling;
    }

    private function initAssets(): void
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueueScripts']);
    }

    /**
     *
     */
    public function enqueueScripts(): void
    {
        $screen = get_current_screen();
        if ($screen->id != 'servebolt_page_servebolt-cache-purge-control') {
            return;
        }
        wp_enqueue_script(
            'servebolt-optimizer-cloudflare-cache-purge-scripts',
            SERVEBOLT_PATH_URL . 'assets/dist/js/cloudflare-cache-purge.js',
            ['servebolt-optimizer-scripts'],
            filemtime(SERVEBOLT_PATH . 'assets/dist/js/cloudflare-cache-purge.js'),
            true
        );
    }

    private function initSettings(): void
    {
        add_action('admin_init', [$this, 'registerSettings']);
    }

    public function registerSettings() : void
    {
        foreach ($this->getSettingsItems() as $key) {
            register_setting('sb-cf-options-page', sb_get_option_name($key));
        }
    }

    /**
     * Settings items for CF cache.
     *
     * @return array
     */
    private function getSettingsItems() : array
    {
        return [
            'cf_switch',
            'cf_zone_id',
            'cf_auth_type',
            'cf_email',
            'cf_api_key',
            'cf_api_token',
            'cf_cron_purge',
        ];
    }
}
