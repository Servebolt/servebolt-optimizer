<?php

namespace Unit;

use WP_UnitTestCase;

class PluginInitTest extends WP_UnitTestCase
{
    public function testThatPluginConstantsAreSet()
    {
        $this->assertTrue(defined('SERVEBOLT_PLUGIN_FILE'));
        $this->assertTrue(defined('SERVEBOLT_PLUGIN_BASENAME'));
        $this->assertTrue(defined('SERVEBOLT_PLUGIN_DIR_URL'));
        $this->assertTrue(defined('SERVEBOLT_PLUGIN_DIR_PATH'));
        $this->assertTrue(defined('SERVEBOLT_PLUGIN_PSR4_PATH'));
    }
}
