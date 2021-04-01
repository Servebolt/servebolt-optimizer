<?php

namespace Unit;

use Servebolt\Optimizer\Utils\DatabaseMigration\MigrationRunner;
use Servebolt\Optimizer\Utils\Queue\Queue;
use ServeboltWPUnitTestCase;

/**
 * Class SqlBuilderTest
 * @package Unit\Queue
 */
class QueueQueryTest extends ServeboltWPUnitTestCase
{

    public function tearDown(): void
    {
        parent::tearDown();
        MigrationRunner::cleanup();
        $this->disallowPersistenceInDatabase();
    }

    public function setUp(): void
    {
        parent::setUp();
        $this->allowPersistenceInDatabase();
        MigrationRunner::migrateFresh();
    }

    public function testQueueQuery()
    {
        $queue = new Queue('my-queue');
        $itemData1 = [
            'foo' => 'bar',
            'bar' => 'foo',
        ];
        $item1 = $queue->add($itemData1);
        $queue->reserveItem($item1);

        $itemData2 = [
            'foo2' => 'bar',
            'bar2' => 'foo',
        ];
        $item2 = $queue->add($itemData2);

        $query = $queue->query();
        $query->selectCount();
        $this->assertEquals(2, $query->result());

        $query->isReserved();
        $this->assertEquals(1, $query->result());

        $queue->reserveItem($item2);
        $this->assertEquals(2, $query->result());

        $query->isCompleted();
        $this->assertEquals(0, $query->result());
    }
}
