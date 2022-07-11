<?php

namespace Unit;

use Servebolt\Optimizer\CachePurge\WpObjectCachePurgeActions\WpObjectCachePurgeActions;
use Servebolt\Optimizer\Utils\DatabaseMigration\MigrationRunner;
use ServeboltWPUnitTestCase;
use Unit\Traits\AttachmentTrait;
use Unit\Traits\CachePurgeTestTrait;

class CachePurgeTriggerTest extends ServeboltWPUnitTestCase
{
    use CachePurgeTestTrait, AttachmentTrait;

    public function setUp() : void
    {
        parent::setUp();
        MigrationRunner::run();
        $this->setUpBogusAcdConfig();
        $this->useQueueBasedCachePurge();
        add_filter('sb_optimizer_automatic_purge_on_post_save', '__return_false'); // Prevent post creation from adding the post to the cache purge queue
        WpObjectCachePurgeActions::reloadEvents();
    }

    public function tearDown() : void
    {
        parent::tearDown();
        MigrationRunner::cleanup();
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

    public function testThatUpdatingAnImageViaAjaxPurgesCache()
    {
        if ($attachmentId = $this->createAttachment('woocommerce-placeholder.png')) {
            // Mimic AJAX post data coming from the image editor
            $_POST = [
                'action' => 'image-editor',
                '_ajax_nonce' => wp_create_nonce('image_editor-' . $attachmentId),
                'postid' => $attachmentId,
                'history' => '[{"r":90}]',
                'target' => 'all',
                'context' => '',
                'do' => 'save',
            ];
            do_action('wp_ajax_image-editor');
            $this->assertEquals(1, did_action('sb_optimizer_purged_post_cache_for_' . $attachmentId));
            $this->deleteAttachment($attachmentId);
        }
    }

    public function testThatUpdatingAnAttachmentsMetaDataPurgesCache()
    {
        if ($attachmentId = $this->createAttachment('woocommerce-placeholder.png')) {
            $attachmentPost = get_post($attachmentId);
            do_action('attachment_updated', $attachmentId, $attachmentPost, $attachmentPost);
            $this->assertEquals(1, did_action('sb_optimizer_purged_post_cache_for_' . $attachmentId));
            $this->deleteAttachment($attachmentId);
        }
    }
}
