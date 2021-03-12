<?php

namespace Unit\Queue;

use Servebolt\Optimizer\Queue\Queue;
use Servebolt\Optimizer\Database\PluginTables;
use ServeboltWPUnitTestCase;


class QueueTest extends ServeboltWPUnitTestCase
{
    public function testThatQueueCanBeCreatedAndThatWeCanAddToTheQueue()
    {
        new PluginTables; // Ensure we got database table

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
