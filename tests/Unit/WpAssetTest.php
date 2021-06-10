<?php

namespace Unit;

use ServeboltWPUnitTestCase;
use Servebolt\Optimizer\Admin\Assets;
use function Servebolt\Optimizer\Helpers\getCurrentPluginVersion;

/**
 * Class WpAssetTest
 * @package Unit
 */
class WpAssetTest extends ServeboltWPUnitTestCase
{

    public function setUp()
    {
        parent::setUp();
        $this->currentPluginVersionNumber = getCurrentPluginVersion(false);
        add_filter('sb_optimizer_should_load_common_assets', '__return_true');
        $this->initAssetsForTest();
    }

    private function initAssetsForTest(): void
    {
        $assets = new Assets;
        $assets->pluginPublicStyling();
        $assets->pluginPublicScripts();
        $assets->pluginAdminStyling();
        $assets->pluginAdminScripts();
        $assets->pluginCommonStyling();
        $assets->pluginCommonScripts();
    }

    public function testThatVersionNumberIsAppliedToUrlOfEnqueuedStyleAssets()
    {
        $this->assertEquals($this->currentPluginVersionNumber, wp_styles()->registered['servebolt-optimizer-public-styling']->ver);
        $this->assertEquals($this->currentPluginVersionNumber, wp_styles()->registered['servebolt-optimizer-styling']->ver);
    }

    public function testThatVersionNumberIsAppliedToUrlOfEnqueuedScriptAssets()
    {
        $this->assertEquals($this->currentPluginVersionNumber, wp_scripts()->registered['servebolt-optimizer-scripts']->ver);
        $this->assertEquals($this->currentPluginVersionNumber, wp_scripts()->registered['servebolt-optimizer-cloudflare-cache-purge-trigger-scripts']->ver);
    }
}
