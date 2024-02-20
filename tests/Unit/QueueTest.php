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
    protected $createPostsNumber = 3000;
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

            if (!empty($item->payload['url']) && $item->payload['url'] === $postPermalink) {
                $urlQueueItem = $item;
            }
            if (!empty($item->payload['url'])) {
                return $item->payload['url'];
            }
            
        }, $items));
        $this->assertContains($postPermalink, $urlOnly);

        $this->assertTrue(isQueueItem($urlQueueItem));
        $this->assertEquals($queueItem->id, $urlQueueItem->parentId);
        $this->assertEquals(WpObjectQueue::$queueName, $urlQueueItem->parentQueueName);
        
        $tags = array_filter(array_map(function($item){
            if (!empty($item->payload['type']) && $item->payload['type'] === 'cachetag') {                
                return $item->payload['tag'];
            }
        }, $items));
        $domainprefix = 'exampleorg-';
        $mutisite_suffix = (is_multisite()) ? '-'.get_current_blog_id() : '';
        $this->assertContains($domainprefix . '00' . $mutisite_suffix, $tags);        
        $this->assertContains($domainprefix . '13-' . date('n') . $mutisite_suffix, $tags);
        $this->assertContains($domainprefix . '14-' . date('Y') . $mutisite_suffix, $tags);
        $this->assertContains($domainprefix . '11-0' . $mutisite_suffix, $tags);
        $this->assertContains($domainprefix . '30' . $mutisite_suffix, $tags);
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
        
        for($i = 0; $i < $this->createPostsNumber; $i++) {
            $payload = [
                'type' => 'url',
                'url' => 'https://test.com/archive/tag/thing/page/' . $i,
            ];
            $queueItem = $wpObjectQueue->add($payload);
            $this->assertTrue(isQueueItem($queueItem));
        }
        // Convert all added items to be failed.
        $sql = "UPDATE {$table} 
        SET attempts = 3
        WHERE completed_at_gmt IS NULL 
        LIMIT {$this->createPostsNumber};";
        $wpdb->query($sql);

        $wpObjectQueue->parseQueue();
        // Check the converted number equals the converted to failed number.        
        $wpdb->get_results("SELECT ID FROM " . $table . " WHERE attempts=3" );
        $this->assertEquals($this->createPostsNumber, $wpdb->num_rows, $this->createPostsNumber . " rows were not sucessfully added to the database" );
        // Setup 150 queue items that have failed.
        $wpObjectQueue->queue->flagMaxAttemptedItemsAsFailed();
        // Look to see if its 150 that have been converted to failed.
        $wpdb->get_results("SELECT ID FROM " . $table . " WHERE attempts=3 AND failed_at_gmt IS NOT NULL" );
        $this->assertEquals(150, $wpdb->num_rows, "150 rows were not converted to failed." );
    }

    /**
     * This test checks if things that have been attempted 3 times are properly coverted to failed.
     * 
     * It adds 300 items to the queue items and then sets them all to be 3 attempts
     * without success. The system should only convert 100 at a time to failed. 
     */
    public function testThatGarbageCollectionWorks(): void
    {

        $this->createPostsNumber = 2000;

        global $wpdb;
        $table = $wpdb->prefix . "sb_queue";

        $wpObjectQueue = WpObjectQueue::getInstance();

        for($i = 0; $i < $this->createPostsNumber; $i++) {
            $payload = [
                'type' => 'url',
                'url' => 'https://test.com/archive/tag/thing/page/' . $i,
            ];
            $queueItem = $wpObjectQueue->add($payload);
            $this->assertTrue(isQueueItem($queueItem));
        }
        // Convert all added items to be failed.
        $finished = strtotime("-3 day");
        $sql = "UPDATE {$table} 
        SET completed_at_gmt = {$finished}
        WHERE completed_at_gmt IS NULL 
        LIMIT {$this->createPostsNumber};";

        $wpdb->query($sql);
        $wpdb->get_results("SELECT ID FROM " . $table . " WHERE completed_at_gmt IS NOT NULL" );
        $this->assertEquals( $this->createPostsNumber, $wpdb->num_rows, " completed count not as expected. expected " . $this->createPostsNumber . " got " .  $wpdb->num_rows);
        
        $wpObjectQueue->garbageCollection();

        $wpdb->get_results("SELECT ID FROM " . $table . " WHERE completed_at_gmt IS NOT NULL" );
        $this->assertEquals(($this->createPostsNumber - 1000), $wpdb->num_rows, "removed completed count not as expected. expected " . ($this->createPostsNumber - 1000) . " got " .  $wpdb->num_rows);

    }

}
