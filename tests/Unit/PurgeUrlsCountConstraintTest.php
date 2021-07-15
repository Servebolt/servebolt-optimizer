<?php

namespace Unit;

use Servebolt\Optimizer\CachePurge\CachePurge;
use ServeboltWPUnitTestCase;
use Servebolt\Optimizer\CachePurge\WordPressCachePurge\PostMethods;
use Unit\Traits\CachePurgeTestTrait;
use function Servebolt\Optimizer\Helpers\updateOption;

/**
 * Class QueueTest
 * @package Unit\Queue
 */
class PurgeUrlsCountConstraintTest extends ServeboltWPUnitTestCase
{
    use PostMethods, CachePurgeTestTrait;

    public function tearDown()
    {
        parent::tearDown();
        remove_filter('sb_optimizer_is_hosted_at_servebolt', '__return_true');
    }

    public function setUp()
    {
        parent::setUp();
        $this->set_permalink_structure('/%postname%/');
        add_filter('sb_optimizer_is_hosted_at_servebolt', '__return_true');
    }

    public function testThatTheNumberOfUrlsGetsRestrictedFromCloudflareDriver()
    {
        $urlsToPurge = array_map(function($item) {
            return 'https://some-url.com/' . $item;
        }, range(1, 150));
        $this->assertCount(150, $urlsToPurge);
        $cloudflareDriver = new CachePurge;
        $urlsToPurge = self::maybeSliceUrlsToPurge($urlsToPurge, 'post', $cloudflareDriver);
        $this->assertCount(30, $urlsToPurge);
    }

    public function testThatTheNumberOfUrlsGetsRestrictedFromAcdDriver()
    {
        $urlsToPurge = array_map(function($item) {
            return 'https://some-url.com/' . $item;
        }, range(1, 600));
        $this->assertCount(600, $urlsToPurge);
        $this->setUpBogusAcdConfig();
        $acdDriver = new CachePurge;
        $urlsToPurge = self::maybeSliceUrlsToPurge($urlsToPurge, 'post', $acdDriver);
        $this->assertCount(500, $urlsToPurge);
    }

    public function testThatTheNumberOfUrlsGetsRestrictedWithFilter()
    {
        $postId = $this->factory()->post->create();
        $urlsToPurge = self::getUrlsToPurgeByPostId($postId);
        $this->assertCount(3, $urlsToPurge);
        $cloudflareDriver = CachePurge::getInstance();
        $urlsToPurge = self::maybeSliceUrlsToPurge($urlsToPurge, 'post', $cloudflareDriver);
        $this->assertCount(3, $urlsToPurge);
        $constraintFunction = function() {
            return 2;
        };
        add_filter('sb_optimizer_max_number_of_urls_to_be_purged', $constraintFunction);
        $urlsToPurge = self::maybeSliceUrlsToPurge($urlsToPurge, 'post', $cloudflareDriver);
        $this->assertCount(2, $urlsToPurge);
        remove_filter('sb_optimizer_max_number_of_urls_to_be_purged', $constraintFunction);
    }
}
