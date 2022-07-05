<?php

namespace Unit;

use Servebolt\Optimizer\Queue\Queues\UrlQueue;
use ServeboltWPUnitTestCase;
use Unit\Traits\CachePurgeTestTrait;
use function Servebolt\Optimizer\Helpers\updateOption;

/**
 * Class UrlQueueTest
 * @package Unit\Queue
 */
class UrlQueueTest extends ServeboltWPUnitTestCase
{
    use CachePurgeTestTrait;

    public function tearDown() : void
    {
        parent::tearDown();
        remove_filter('sb_optimizer_is_hosted_at_servebolt', '__return_true');
    }

    public function setUp() : void
    {
        parent::setUp();
        add_filter('sb_optimizer_is_hosted_at_servebolt', '__return_true');
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
