<?php

namespace Unit;

use WP_UnitTestCase;
use Servebolt\Optimizer\Admin\GeneralSettings\GeneralSettings;
use function Servebolt\Optimizer\Helpers\deleteOption;
use function Servebolt\Optimizer\Helpers\updateOption;

class GeneralSettingsTest extends WP_UnitTestCase
{
    public function testThatGeneralSettingsCanResolveSetting()
    {
        $generalSettings = GeneralSettings::getInstance();
        $this->assertFalse($generalSettings->assetAutoVersion());
        updateOption('asset_auto_version', true);
        $this->assertTrue($generalSettings->assetAutoVersion());
        deleteOption('asset_auto_version');
        $this->assertFalse($generalSettings->assetAutoVersion());
    }

    public function testThatGeneralSettingOverrideWorks()
    {
        $generalSettings = GeneralSettings::getInstance();
        $this->assertFalse($generalSettings->assetAutoVersion());
        define('SERVEBOLT_ASSET_AUTO_VERSION', true);
        $this->assertTrue($generalSettings->assetAutoVersion());
    }
}
