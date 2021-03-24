<?php

namespace Unit;

use Servebolt\Optimizer\Queue\QueueSystem\Queue;
use Servebolt\Optimizer\Queue\Queues\UrlQueue;
use Servebolt\Optimizer\Queue\Queues\WpObjectQueue;
use Servebolt\Optimizer\Database\PluginTables;
use ServeboltWPUnitTestCase;

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
        new PluginTables; // Ensure we got database table
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
        $this->assertInstanceOf('\\Servebolt\\Optimizer\\Queue\\QueueSystem\\QueueItem', $queueItem);
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

        $this->assertInstanceOf('\\Servebolt\\Optimizer\\Queue\\QueueSystem\\QueueItem', $urlQueueItem);
        $this->assertEquals($queueItem->id, $urlQueueItem->parentId);
        $this->assertEquals(WpObjectQueue::$queueName, $urlQueueItem->parentQueueName);
    }
}
