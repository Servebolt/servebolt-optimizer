<?php

namespace Servebolt\Optimizer\Admin\CachePurge;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use Servebolt\Optimizer\Admin\Ajax\CachePurge\Configuration;
use Servebolt\Optimizer\Admin\Ajax\CachePurge\PurgeActions;
use Servebolt\Optimizer\Admin\Ajax\CachePurge\QueueHandling;
use Servebolt\Optimizer\Traits\Singleton;
use Servebolt\Optimizer\Helpers as Helpers;

class CachePurge
{

    use Singleton;

    /**
     * CachePurge constructor.
     */
    public function __construct()
    {
        $this->initAjax();
        $this->initAssets();
        $this->initSettings();
    }

    public function render()
    {
        Helpers\view('cache-purge/cache-purge');
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
        if ($screen->id != 'servebolt_page_servebolt-cf-cache-control') {
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
