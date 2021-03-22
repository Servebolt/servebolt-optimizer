<?php

namespace Unit\Queue;

use Servebolt\Optimizer\Queue\Queue;
use Servebolt\Optimizer\Queue\QueueItem;
use Servebolt\Optimizer\Database\PluginTables;
use ServeboltWPUnitTestCase;


class QueueTest extends ServeboltWPUnitTestCase
{

    public function setUp()
    {
        parent::setUp();
        new PluginTables; // Ensure we got database table
    }

    public function testThatItemCanBeAddedToTheQueue()
    {
        $itemData = [
            'foo' => 'bar',
            'bar' => 'foo',
        ];
        $queue = new Queue('my-queue');
        $item = $queue->add($itemData);
        $this->assertTrue(is_a($item, '\\Servebolt\\Optimizer\\Queue\\QueueItem'));
        $this->assertTrue($queue->itemExists($item->id));
    }

    public function testThatPayloadGetsSet()
    {
        $itemData = [
            'foo' => 'bar',
            'bar' => 'foo',
        ];
        $queue = new Queue('my-queue');
        $item = $queue->add($itemData);
        $this->assertEquals($itemData, $item->payload);
    }

    public function testThatWeCanGetAndReadItemFromQueue()
    {
        $queue = new Queue('my-queue');
        $queue->add([
            'foo' => 'bar',
            'bar' => 'foo',
        ]);
        $payload = [
            'foo' => 'bar2',
            'bar' => 'foo2',
        ];
        $item = $queue->add($payload);
        $queue->add([
            'foo' => 'bar3',
            'bar' => 'foo3',
        ]);
        $lookupItem = $queue->get($item->id);
        $this->assertTrue(is_a($lookupItem, '\\Servebolt\\Optimizer\\Queue\\QueueItem'));
        $this->assertEquals($lookupItem->id, $item->id);
        $this->assertEquals($payload, $lookupItem->payload);
    }

    public function testThatItemCountIsCorrect()
    {
        $itemData = [
            'foo' => 'bar',
            'bar' => 'foo',
        ];
        $queue = new Queue('my-queue');
        $queue->add($itemData);
        $queue->add($itemData);
        $queue->add($itemData);
        $this->assertEquals(3, $queue->countItems());
    }

    public function testThatWeCanReachItemPropertiesWithBothCamelAndSnakeCase()
    {
        $itemData = [
            'foo' => 'bar',
            'bar' => 'foo',
        ];
        $queue = new Queue('my-queue');
        $item = $queue->add($itemData);
        $item->reserve();
        $this->assertEquals($item->createdAtGmt, $item->created_at_gmt);
        $this->assertEquals($item->reservedAtGmt, $item->reserved_at_gmt);
        $this->assertEquals($item->parent_id, $item->parentId);
    }

    public function testThatWeCanHaveMultipleQueues()
    {
        $firstInstance = Queue::getInstance('my-queue-1', 'my-queue-1');
        $secondInstance = Queue::getInstance('my-queue-2', 'my-queue-2');
        $thirdInstance = new Queue('my-queue-3');
        $fourthInstance = Queue::getInstance(null, 'my-queue-4');
        $itemData = [
            'foo' => 'bar',
            'bar' => 'foo',
        ];

        $item = $firstInstance->add($itemData);
        $this->assertTrue($firstInstance->itemExists($item->id));
        $this->assertFalse($secondInstance->itemExists($item->id));
        $this->assertFalse($thirdInstance->itemExists($item->id));
        $this->assertFalse($fourthInstance->itemExists($item->id));

        $item = $secondInstance->add($itemData);
        $this->assertTrue($secondInstance->itemExists($item->id));
        $this->assertFalse($firstInstance->itemExists($item->id));
        $this->assertFalse($thirdInstance->itemExists($item->id));
        $this->assertFalse($fourthInstance->itemExists($item->id));
    }

    public function testThatWeCanDeleteItem()
    {
        $queue = new Queue('my-queue');
        $itemData = [
            'foo' => 'bar',
            'bar' => 'foo',
        ];
        $item = $queue->add($itemData);
        $this->assertEquals(1, $queue->countItems());
        $this->assertTrue($queue->itemExists($item->id));
        $queue->delete($item);
        $this->assertEquals(0, $queue->countItems());
        $this->assertFalse($queue->itemExists($item->id));
    }

    public function testThatWeCanDeleteItems()
    {
        $queue = new Queue('my-queue');
        $itemData = [
            'foo' => 'bar',
            'bar' => 'foo',
        ];
        $queue->add($itemData);
        $queue->add($itemData);
        $queue->add($itemData);
        
    }

    public function testThatQueueCanBeCreatedAndThatWeCanAddToTheQueue()
    {
        $itemData = [
            'foo' => 'bar',
            'bar' => 'foo',
        ];
        $queueName = 'my-queue';
        $queue = new Queue($queueName);

        $this->assertEquals(0, $queue->countItems());
        $item = $queue->add($itemData);
        $this->assertEquals(1, $queue->countItems());
        $currentTime = current_time('timestamp', true);
        $delay = 1;

        $this->assertEquals($currentTime, $item->created_at_gmt);
        $this->assertEquals($currentTime, $item->createdAtGmt);
        $this->assertTrue($queue->itemExists($item->id));
        $this->assertTrue(is_array($item->payload));
        $this->assertEquals($itemData, $item->payload);
        $this->assertEquals($queueName, $item->queue);
        $this->assertEquals(0, $item->attempts);

        sleep($delay);
        $this->assertFalse($item->isReserved());
        $item = $queue->reserveItem($item);
        $this->assertNotFalse($item);
        $this->assertTrue($item->isReserved());
        $this->assertEquals($currentTime + $delay, $item->reserved_at_gmt);
        $this->assertEquals(1, $item->attempts);

        $item = $queue->releaseItem($item);
        $this->assertNotFalse($item);
        $this->assertFalse($item->isReserved());
        $this->assertNull($item->reserved_at_gmt);
        $this->assertEquals(1, $item->attempts);

        $queue->delete($item);
        $this->assertFalse($queue->itemExists($item->id));
        $this->assertEquals(0, $queue->countItems());
    }
}
