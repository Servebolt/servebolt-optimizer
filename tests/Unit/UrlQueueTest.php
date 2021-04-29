<?php

namespace Unit;

use Servebolt\Optimizer\Queue\Queues\UrlQueue;
use ServeboltWPUnitTestCase;
use function Servebolt\Optimizer\Helpers\updateOption;

/**
 * Class UrlQueueTest
 * @package Unit\Queue
 */
class UrlQueueTest extends ServeboltWPUnitTestCase
{
    public function tearDown()
    {
        parent::tearDown();
        remove_filter('sb_optimizer_is_hosted_at_servebolt', '__return_true');
    }

    public function setUp()
    {
        parent::setUp();
        add_filter('sb_optimizer_is_hosted_at_servebolt', '__return_true');
    }

    private function setUpBogusAcdConfig(): void
    {
        add_filter('sb_optimizer_selected_cache_purge_driver', function() {
            return 'acd';
        });
        add_filter('sb_optimizer_acd_is_configured', '__return_true');
        updateOption('cache_purge_switch', true);
        updateOption('cache_purge_auto', true);
    }

    public function testThatUrlChunkSizeIsSetAccordingToDriver()
    {
        $instance = UrlQueue::getInstance();
        $this->assertEquals(30, $instance->getUrlChunkSize());
        UrlQueue::destroyInstance();
        $this->setUpBogusAcdConfig();
        $instance = UrlQueue::getInstance();
        $this->assertEquals(500, $instance->getUrlChunkSize());
    }
}
