<?php

namespace Unit;

use Servebolt\Optimizer\CachePurge\WpObjectCachePurgeActions\WpObjectCachePurgeActions;
use Servebolt\Optimizer\Utils\DatabaseMigration\MigrationRunner;
use ServeboltWPUnitTestCase;
use Unit\Traits\CachePurgeTestTrait;

class CachePurgeTriggerTest extends ServeboltWPUnitTestCase
{
    use CachePurgeTestTrait;

    public function setUp()
    {
        parent::setUp();
        MigrationRunner::run(true);
        $this->setUpBogusAcdConfig();
        $this->useQueueBasedCachePurge();
        add_filter('sb_optimizer_automatic_purge_on_post_save', '__return_false'); // Prevent post creation from adding the post to the cache purge queue
        WpObjectCachePurgeActions::reloadEvents();
    }

    public function tearDown()
    {
        parent::tearDown();
        remove_filter('sb_optimizer_automatic_purge_on_post_save', '__return_false');
    }

    public function testThatTrashingAPostPurgesCache()
    {
        $postId = $this->factory()->post->create();
        $actionCount = did_action('wp_trash_post');
        wp_delete_post($postId);
        $this->assertEquals($actionCount + 1, did_action('wp_trash_post'));
        $this->assertEquals(1, did_action('sb_optimizer_purged_post_cache_for_' . $postId));
    }

    public function testThatDeletingAPostAPostPurgesCache()
    {
        $postId = $this->factory()->post->create();
        $actionCount = did_action('before_delete_post');
        wp_delete_post($postId, true);
        $this->assertEquals($actionCount + 1, did_action('before_delete_post'));
        $this->assertEquals(1, did_action('sb_optimizer_purged_post_cache_for_' . $postId));
    }
}
