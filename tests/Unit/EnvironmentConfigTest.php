<?php

namespace Unit;

use Servebolt\Optimizer\Utils\EnvironmentConfig;
use WP_UnitTestCase;
use function Servebolt\Optimizer\Helpers\arrayGet;

class EnvironmentConfigTest extends WP_UnitTestCase
{
    public function testThatWeCanGetFromEnvironmentConfig()
    {
        $url = arrayGet('ENV_CONFIG_TEST_URL', $_ENV);
        if (!$url) {
            $this->markTestSkipped('This test is not configured (missing .env file?).');
        }
        add_filter('sb_optimizer_environment_config_base_url', function () use ($url) {
            return $url;
        });
        add_filter('sb_optimizer_is_accelerated_domains', '__return_true');
        add_filter('sb_optimizer_is_hosted_at_servebolt', '__return_true');
        $config = new EnvironmentConfig;
        $this->assertTrue($config->get('sb_acd_image_resize'));
        remove_all_filters('sb_optimizer_environment_config_base_url');
        remove_all_filters('sb_optimizer_is_accelerated_domains');
        remove_all_filters('sb_optimizer_is_hosted_at_servebolt');
    }

    public function testThatWeCanGetFromEnvironmentConfigUsingDummyData()
    {
        add_filter('sb_optimizer_is_accelerated_domains', '__return_true');
        add_filter('sb_optimizer_is_hosted_at_servebolt', '__return_true');
        $config = new EnvironmentConfig;
        $this->assertNull($config->get('some-config'));
        $data = [
            'some-config' => 'some-value',
            'another-config' => 'another-value',
        ];
        $config->setConfigObject((object) $data);
        $this->assertEquals('some-value', $config->get('some-config'));
        $this->assertEquals('another-value', $config->get('another-config'));
        remove_all_filters('sb_optimizer_is_accelerated_domains');
        remove_all_filters('sb_optimizer_is_hosted_at_servebolt');
    }

}
