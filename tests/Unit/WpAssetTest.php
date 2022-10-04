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

    public function setUp() : void
    {
        parent::setUp();
        $this->currentPluginVersionNumber = getCurrentPluginVersion(false);
        add_filter('sb_optimizer_should_load_common_assets', '__return_true');
        $this->initAssetsForTest();
    }

    public function testThatVersionNumberIsAppliedToUrlOfEnqueuedStyleAssets()
    {
        $this->assertArrayHasKey('servebolt-optimizer-public-styling', wp_styles()->registered);
        $this->assertEquals($this->currentPluginVersionNumber, wp_styles()->registered['servebolt-optimizer-public-styling']->ver);
        $this->assertArrayHasKey('servebolt-optimizer-styling', wp_styles()->registered);
        $this->assertEquals($this->currentPluginVersionNumber, wp_styles()->registered['servebolt-optimizer-styling']->ver);
    }

    public function testThatVersionNumberIsAppliedToUrlOfEnqueuedScriptAssets()
    {
        $this->assertArrayHasKey('servebolt-optimizer-scripts', wp_scripts()->registered);
        $this->assertEquals($this->currentPluginVersionNumber, wp_scripts()->registered['servebolt-optimizer-scripts']->ver);
        $this->assertArrayHasKey('servebolt-optimizer-cache-purge-trigger-scripts', wp_scripts()->registered);
        $this->assertEquals($this->currentPluginVersionNumber, wp_scripts()->registered['servebolt-optimizer-cache-purge-trigger-scripts']->ver);
    }

    public function testFilemtimeFallbackForVersionParameterOnEnqueuedAssets()
    {
        $this->dequeueAssets();
        add_filter('sb_optimizer_static_asset_plugin_version', '__return_null');
        $this->initAssetsForTest();
        
        $this->assertEquals(filemtime($this->getAssetPath(wp_styles()->registered['servebolt-optimizer-public-styling']->src)), wp_styles()->registered['servebolt-optimizer-public-styling']->ver);
        $this->assertEquals(filemtime($this->getAssetPath(wp_styles()->registered['servebolt-optimizer-styling']->src)), wp_styles()->registered['servebolt-optimizer-styling']->ver);

        $this->assertEquals(filemtime($this->getAssetPath(wp_scripts()->registered['servebolt-optimizer-scripts']->src)), wp_scripts()->registered['servebolt-optimizer-scripts']->ver);
        $this->assertEquals(filemtime($this->getAssetPath(wp_scripts()->registered['servebolt-optimizer-cache-purge-trigger-scripts']->src)), wp_scripts()->registered['servebolt-optimizer-cache-purge-trigger-scripts']->ver);
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

    private function dequeueAssets()
    {
        wp_deregister_style('servebolt-optimizer-public-styling');
        wp_deregister_style('servebolt-optimizer-styling');
        wp_deregister_script('servebolt-optimizer-scripts');
        wp_deregister_script('servebolt-optimizer-cache-purge-trigger-scripts');
    }

    private function getAssetPath($url): string
    {
        
        preg_match('/servebolt-optimizer(.+?(?=\/))\/(.*)/', $url, $matches);        
        if (isset($matches[2])) {
            /**
             * adapted form 
             * 
             * return SERVEBOLT_PLUGIN_DIR_PATH . trim($matches[2], '/');
             * 
             * to the new structure and adds 'assets/' to the path to make it work
             * 
             */
            return SERVEBOLT_PLUGIN_DIR_PATH . trim($matches[1], '/'). '/' . trim($matches[2], '/');
        }
        return false;
    }
}
