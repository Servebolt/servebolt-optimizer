<?php

namespace Unit\Queue;

use Servebolt\Optimizer\Queue\Queue;
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

    public function testThatQueueNameGetSet()
    {
        $itemData = [
            'foo' => 'bar',
            'bar' => 'foo',
        ];
        $queueName = 'my-queue';
        $queue = new Queue($queueName);
        $item = $queue->add($itemData);
        $this->assertEquals($queueName, $item->queue);
    }

    public function testThatWeCanReachItemProperties()
    {
        $itemData = [
            'foo' => 'bar',
            'bar' => 'foo',
        ];
        $queue = new Queue('my-queue');
        $item = $queue->add($itemData);
        $currentTime = current_time('timestamp', true);
        $this->assertIsInt($item->id);
        $this->assertNull($item->parent_id);
        $this->assertNull($item->parent_queue_name);
        $this->assertEquals($itemData, $item->payload);
        $this->assertEquals(0, $item->attempts);
        $this->assertNull($item->reserved_at_gmt);
        $this->assertNull($item->completed_at_gmt);
        $this->assertEquals($currentTime, $item->created_at_gmt);
    }

    public function testThatPayloadGetsSet()
    {
        $itemData = [
            'foo' => 'bar',
            'bar' => 'foo',
        ];
        $queue = new Queue('my-queue');
        $item = $queue->add($itemData);
        $this->assertTrue(is_array($item->payload));
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
        $this->assertEquals(0, $queue->countItems());
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
        $firstItem = $queue->add($itemData);
        $secondItem = $queue->add($itemData);
        $thirdItem = $queue->add($itemData);
        $this->assertEquals(3, $queue->countItems());
        $queue->delete($firstItem->id);
        $this->assertEquals(2, $queue->countItems());
        $queue->delete($secondItem->id);
        $this->assertEquals(1, $queue->countItems());
        $queue->delete($thirdItem->id);
        $this->assertTrue($queue->isEmpty());
    }

    public function testThatItemCanHaveParentItem()
    {
        $firstInstance = Queue::getInstance('my-queue-1', 'my-queue-1');
        $secondInstance = Queue::getInstance('my-queue-2', 'my-queue-2');
        $itemData = [
            'foo' => 'bar',
            'bar' => 'foo',
        ];

        $parentItem = $firstInstance->add($itemData);
        $childItem = $secondInstance->add($itemData);
        $childItem->addParent($parentItem);
        $this->assertEquals($parentItem, $childItem->getParentItem());

        $childItem = $secondInstance->add($itemData, $parentItem);
        $this->assertEquals($parentItem, $childItem->getParentItem());
    }

    public function testThatWeCanClearQueue()
    {
        $queue = new Queue('my-queue');
        $itemData = [
            'foo' => 'bar',
            'bar' => 'foo',
        ];
        $queue->add($itemData);
        $queue->add($itemData);
        $queue->add($itemData);
        $this->assertEquals(3, $queue->countItems());
        $queue->clearQueue();
        $this->assertEquals(0, $queue->countItems());
    }

    public function testThatTimestampAreCorrect()
    {
        $itemData = [
            'foo' => 'bar',
            'bar' => 'foo',
        ];
        $queueName = 'my-queue';
        $queue = new Queue($queueName);

        $item = $queue->add($itemData);
        $currentTime = current_time('timestamp', true);

        $this->assertEquals($currentTime, $item->createdAtGmt);
        sleep(1);
        $item = $queue->reserveItem($item);
        $this->assertEquals($currentTime + 1, $item->reservedAtGmt);
        sleep(1);
        $item = $queue->completeItem($item);
        $this->assertEquals($currentTime + 2, $item->completedAtGmt);
    }

    public function testThatWeCanReserveItem()
    {
        $queue = new Queue('my-queue');
        $itemData = [
            'foo' => 'bar',
            'bar' => 'foo',
        ];
        $item = $queue->add($itemData);
        $this->assertFalse($item->isReserved());
        $item = $queue->reserveItem($item);
        $this->assertTrue($item->isReserved());
    }

    public function testThatWeCanReserveItems()
    {
        $queue = new Queue('my-queue');
        $itemData = [
            'foo' => 'bar',
            'bar' => 'foo',
        ];
        $items = [
            $queue->add($itemData),
            $queue->add($itemData),
            $queue->add($itemData),
        ];
        $this->assertEquals(0, $queue->countReservedItems());
        $this->assertEquals(3, $queue->countAvailableItems());
        $queue->reserveItems($items);
        $this->assertEquals(3, $queue->countReservedItems());
        $this->assertEquals(0, $queue->countAvailableItems());
    }

    public function testThatWeCanCompleteItem()
    {
        $queue = new Queue('my-queue');
        $itemData = [
            'foo' => 'bar',
            'bar' => 'foo',
        ];
        $item = $queue->add($itemData);
        $this->assertFalse($item->isReserved());
        $this->assertFalse($item->isCompleted());
        $item = $queue->completeItem($item);
        $this->assertTrue($item->isReserved());
        $this->assertTrue($item->isCompleted());
    }

    public function testThatWeCanCompleteItems()
    {
        $queue = new Queue('my-queue');
        $itemData = [
            'foo' => 'bar',
            'bar' => 'foo',
        ];
        $items = [
            $queue->add($itemData),
            $queue->add($itemData),
            $queue->add($itemData),
        ];
        $this->assertEquals(0, $queue->countCompletedItems());
        $this->assertEquals(3, $queue->countAvailableItems());
        $queue->completeItems($items);
        $this->assertEquals(3, $queue->countCompletedItems());
        $this->assertEquals(0, $queue->countAvailableItems());
    }

    public function testThatAttemptIncrements()
    {
        $queue = new Queue('my-queue');
        $itemData = [
            'foo' => 'bar',
            'bar' => 'foo',
        ];
        $item = $queue->add($itemData);
        $this->assertEquals(0, $item->attempts);
        $item = $queue->reserveItem($item);
        $this->assertEquals(1, $item->attempts);
        $item->doAttempt();
        $this->assertEquals(2, $item->attempts);
    }
}
