<?php

namespace Unit;

use Servebolt\Optimizer\Utils\Queue\Queue;
use Servebolt\Optimizer\Queue\Queues\UrlQueue;
use Servebolt\Optimizer\Queue\Queues\WpObjectQueue;
use Servebolt\Optimizer\Utils\DatabaseMigration\MigrationRunner;
use ServeboltWPUnitTestCase;
use function Servebolt\Optimizer\Helpers\isQueueItem;

/**
 * Class QueueTest
 * @package Unit\Queue
 */
class QueueTest extends ServeboltWPUnitTestCase
{
    public function setUp() : void
    {
        parent::setUp();
        $this->set_permalink_structure('/%postname%/');
        MigrationRunner::run();
    }

    public function tearDown(): void
    {
        parent::tearDown();
        MigrationRunner::cleanup();
    }

    public function testThatPostUrlsGetsParsedFromWpObjectQueueToUrlQueue(): void
    {
        $postId = $this->factory()->post->create();
        $postPermalink = get_permalink($postId);
        $this->assertIsInt($postId);
        $wpObjectQueue = WpObjectQueue::getInstance();
        $urlQueueInstance = Queue::getInstance(UrlQueue::$queueName);
        $queueItem = $wpObjectQueue->add([
            'id' => $postId,
            'type' => 'post',
        ]);
        $this->assertTrue(isQueueItem($queueItem));
        $wpObjectQueue->parseQueue();

        $items = $urlQueueInstance->getItems();

        $urlQueueItem = null;
        $this->assertIsArray($items);
        $urlOnly = array_filter(array_map(function($item) use ($postPermalink, &$urlQueueItem) {
            if ($item->payload['url'] === $postPermalink) {
                $urlQueueItem = $item;
            }
            if (!empty($item->payload['url'])) {
                return $item->payload['url'];
            }
            return null;
        }, $items));
        $this->assertContains($postPermalink, $urlOnly);

        $this->assertTrue(isQueueItem($urlQueueItem));
        $this->assertEquals($queueItem->id, $urlQueueItem->parentId);
        $this->assertEquals(WpObjectQueue::$queueName, $urlQueueItem->parentQueueName);
    }

    public function testThatPostTagsGetsParsedFromWpObjectQueueToUrlQueue(): void
    {
        $postId = $this->factory()->post->create();
        $postPermalink = get_permalink($postId);
        $this->assertIsInt($postId);
        $wpObjectQueue = WpObjectQueue::getInstance();
        $urlQueueInstance = Queue::getInstance(UrlQueue::$queueName);
        $queueItem = $wpObjectQueue->add([
            'id' => $postId,
            'type' => 'cachetag',
        ]);
        $this->assertTrue(isQueueItem($queueItem));
        $wpObjectQueue->parseQueue();

        $items = $urlQueueInstance->getItems();

        $urlQueueItem = null;
        $this->assertIsArray($items);
        $urlOnly = array_filter(array_map(function($item) use ($postPermalink, &$urlQueueItem) {
            error_log("show the of payload");
            error_log(print_r($item->payload, true));
            if ($item->payload['url'] === $postPermalink) {
                $urlQueueItem = $item;
            }
            if (!empty($item->payload['url'])) {
                return $item->payload['url'];
            }
            return null;
        }, $items));
        $this->assertContains($postPermalink, $urlOnly);

        $this->assertTrue(isQueueItem($urlQueueItem));
        $this->assertEquals($queueItem->id, $urlQueueItem->parentId);
        $this->assertEquals(WpObjectQueue::$queueName, $urlQueueItem->parentQueueName);
    }

    public function testThatTermUrlsGetsParsedFromWpObjectQueueToUrlQueue(): void
    {
        $taxonomy = 'test-taxonomy';
        $termName = 'test-term';

        register_taxonomy($taxonomy, null);
        $term = wp_insert_term($termName, $taxonomy);
        $termId = $term['term_id'];
        $termPermalink = get_term_link($termId, $taxonomy);

        $this->assertIsInt($termId);
        $wpObjectQueue = WpObjectQueue::getInstance();
        $urlQueueInstance = Queue::getInstance(UrlQueue::$queueName);
        $queueItem = $wpObjectQueue->add([
            'id' => $termId,
            'type' => 'term',
        ]);
        $this->assertTrue(isQueueItem($queueItem));
        $wpObjectQueue->parseQueue();

        $items = $urlQueueInstance->getItems();

        $urlQueueItem = null;
        $this->assertIsArray($items);
        $urlOnly = array_filter(array_map(function($item) use ($termPermalink, &$urlQueueItem) {
            if ($item->payload['url'] === $termPermalink) {
                $urlQueueItem = $item;
            }
            if (!empty($item->payload['url'])) {
                return $item->payload['url'];
            }
            return null;
        }, $items));
        $this->assertContains($termPermalink, $urlOnly);

        $this->assertTrue(isQueueItem($urlQueueItem));
        $this->assertEquals($queueItem->id, $urlQueueItem->parentId);
        $this->assertEquals(WpObjectQueue::$queueName, $urlQueueItem->parentQueueName);
    }


    /**
     * This test checks if things that have been attempted 3 times are properly coverted to failed.
     * 
     * It adds 300 items to the queue items and then sets them all to be 3 attempts
     * without success. The system should only convert 100 at a time to failed. 
     */
    public function testThatFailedQueueItemIsRemoved(): void
    {
        
        global $wpdb;
        $table = $wpdb->prefix . "sb_queue";

        $wpObjectQueue = WpObjectQueue::getInstance();
        
        for($i = 0; $i < 3000; $i++) {
            $payload = [
                'type' => 'url',
                'url' => 'https://test.com/archive/tag/thing/page/' . $i,
            ];
            $queueItem = $wpObjectQueue->add($payload);
            $this->assertTrue(isQueueItem($queueItem));
        }
        
        $sql = "UPDATE {$table} 
        SET attempts = 3
        WHERE completed_at_gmt IS NULL 
        LIMIT 3000;";

        $wpdb->query($sql);
        $wpObjectQueue->parseQueue();

        global $wpdb;
        $wpdb->get_results("SELECT ID FROM " . $table . " WHERE attempts=3" );
        $this->assertEquals(3000, $wpdb->num_rows, "3000 rows were not sucessfully added to the database" );

        $wpObjectQueue->queue->flagMaxAttemptedItemsAsFailed();

        $wpdb->get_results("SELECT ID FROM " . $table . " WHERE attempts=3 AND failed_at_gmt IS NOT NULL" );
        $this->assertEquals(150, $wpdb->num_rows, "150 rows were not converted to failed." );
    }
}
