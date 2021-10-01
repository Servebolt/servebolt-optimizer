<?php

namespace Unit;

use Servebolt\Optimizer\Utils\EnvironmentConfig;
use WP_UnitTestCase;

class EnvironmentConfigTest extends WP_UnitTestCase
{
    public function testThatWeCanGetFromEnvironmentConfig()
    {
        $config = EnvironmentConfig::getInstance();
        /*
        add_filter('sb_optimizer_environment_config_base_url', function () {
            return 'https://www.servebolt.com';
        });
        $this->assertTrue($config->get('sb_acd_image_resize'));
        */
        $this->assertNull($config->get('some-config'));
        $data = [
            'some-config' => 'some-value',
            'another-config' => 'another-value',
        ];
        $config->setConfigObject((object) $data);
        $this->assertEquals('some-value', $config->get('some-config'));
        $this->assertEquals('another-value', $config->get('another-config'));
        /*
        remove_all_filters('sb_optimizer_environment_config_base_url');
        $config->setConfigObject(null);
        */
    }

}
