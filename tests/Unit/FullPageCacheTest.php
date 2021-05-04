<?php

namespace Unit;

use Servebolt\Optimizer\FullPageCache\CachePostExclusion;
use Servebolt\Optimizer\FullPageCache\FullPageCacheHeaders;
use Servebolt\Optimizer\FullPageCache\FullPageCacheSettings;
use ServeboltWPUnitTestCase;

/**
 * Class FullPageCacheTest
 */
class FullPageCacheTest extends ServeboltWPUnitTestCase
{
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
        FullPageCacheHeaders::destroyInstance();
        $instance = FullPageCacheHeaders::getInstance();
        FullPageCacheHeaders::mock();
        $instance->headersAlreadySet(false);
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
}
