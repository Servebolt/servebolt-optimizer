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
    public function setUp()
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
}
