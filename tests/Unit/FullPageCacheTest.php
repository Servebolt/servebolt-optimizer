<?php

namespace Unit;

use Servebolt\Optimizer\Admin\FullPageCacheControl\Ajax\FpcPostExclusion;
use Servebolt\Optimizer\FullPageCache\CachePostExclusion;
use Servebolt\Optimizer\FullPageCache\FullPageCache;
use Servebolt\Optimizer\FullPageCache\FullPageCacheHeaders;
use Servebolt\Optimizer\FullPageCache\FullPageCacheSettings;
use Servebolt\Optimizer\Queue\Queues\WpObjectQueue;
use Servebolt\Optimizer\Utils\DatabaseMigration\MigrationRunner;
use ServeboltWPUnitTestCase;
use Unit\Traits\CachePurgeTestTrait;

/**
 * Class FullPageCacheTest
 */
class FullPageCacheTest extends ServeboltWPUnitTestCase
{
    use CachePurgeTestTrait;

    public function setUp()
    {
        parent::setUp();
        MigrationRunner::run(); // We need the custom tables for the queue system to work
    }

    public function tearDown()
    {
        parent::tearDown();
        MigrationRunner::cleanup();
    }

    public function testThatPostGetsExcludedFromCache()
    {
        $postId = 1;
        $this->assertFalse(CachePostExclusion::shouldExcludePostFromCache($postId));
        $this->assertTrue(CachePostExclusion::excludePostFromCache($postId));
        $this->assertTrue(CachePostExclusion::shouldExcludePostFromCache($postId));
        $this->assertFalse(CachePostExclusion::shouldExcludePostFromCache(2));
        $this->assertEquals([$postId], CachePostExclusion::getIdsToExcludeFromCache());
        $this->assertTrue(CachePostExclusion::clearExcludePostFromCache());
        $this->assertEquals([], CachePostExclusion::getIdsToExcludeFromCache());
        $this->assertFalse(CachePostExclusion::shouldExcludePostFromCache($postId));
        $this->assertTrue(CachePostExclusion::excludePostsFromCache([$postId]));
        $this->assertTrue(CachePostExclusion::shouldExcludePostFromCache($postId));
        $this->assertTrue(CachePostExclusion::setIdsToExcludeFromCache([1, 2]));
        $this->assertEquals([1, 2], CachePostExclusion::getIdsToExcludeFromCache());
    }

    private function setupHeaderTest()
    {
        add_filter('sb_optimizer_fpc_should_debug_headers', '__return_true');
        FullPageCacheHeaders::destroyInstance();
        $instance = FullPageCacheHeaders::getInstance();
        FullPageCacheHeaders::mock();
        $instance->shouldSetCacheHeaders(false);
        return $instance;
    }

    public function testThatCacheHeadersGetSetWhenPostIsExcludedFromCache()
    {
        FullPageCacheSettings::fpcToggleActive(true);
        $instance = $this->setupHeaderTest();

        $postId = $this->factory()->post->create();
        CachePostExclusion::excludePostFromCache($postId);
        $GLOBALS['post'] = get_post($postId);

        $instance->setHeaders([get_post($postId)]);
        do_action('send_headers');
        $headers = FullPageCacheHeaders::getMockHeaders();

        $this->assertContains('No-cache-trigger: 3', $headers);
        $this->assertContains('Cache-Control: max-age=0,no-cache,s-maxage=0', $headers);
        $this->assertContains('Pragma: no-cache', $headers);
    }

    public function testThatCacheHeadersGetSetEvenThoFpcIsInActive()
    {
        $instance = $this->setupHeaderTest();

        $instance->setHeaders([get_post(1)]);
        do_action('send_headers');

        $headers = FullPageCacheHeaders::getMockHeaders();
        $this->assertContains('No-cache-trigger: 1', $headers);
        $this->assertContains('Cache-Control: max-age=0,no-cache,s-maxage=0', $headers);
        $this->assertContains('Pragma: no-cache', $headers);
    }

    public function testThatPostGetsCachePurgedWhenAddedToFpcExclusion()
    {
        FullPageCache::destroyInstance();
        $this->useQueueBasedCachePurge();
        $this->setUpBogusAcdConfig();
        FullPageCache::getInstance();
        $postId = $this->factory()->post->create();
        $fpcPostExclusion = new FpcPostExclusion();
        $result = $fpcPostExclusion->addItemsFromFpcExclusion([
            $postId
        ]);
        $this->assertEquals(1, did_action('sb_optimizer_post_added_to_fpc_exclusion'));
        $this->assertTrue($result['success']);
        $wpObjectQueue = WpObjectQueue::getInstance();
        $this->assertTrue($wpObjectQueue->hasPostInQueue($postId));
    }
}
