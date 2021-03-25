<?php

namespace Unit;

use Servebolt\Optimizer\Database\MigrationRunner;
use Servebolt\Optimizer\Queue\QueueSystem\Queue;
use Servebolt\Optimizer\SqlBuilder\SqlBuilder;
use ServeboltWPUnitTestCase;

/**
 * Class SqlBuilderTest
 * @package Unit\Queue
 */
class SqlBuilderTest extends ServeboltWPUnitTestCase
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

    public function testThatQueryBuilderWorks()
    {
        $queue = new Queue('my-queue');
        $itemData1 = [
            'foo' => 'bar',
            'bar' => 'foo',
        ];
        $item1 = $queue->add($itemData1);
        $queue->reserveItem($item1);

        $itemData2 = [
            'foo' => 'bar',
            'bar' => 'foo',
        ];
        $item2 = $queue->add($itemData2);
        $queue->reserveItem($item2);

        $query = SqlBuilder::query('sb_queue');
        $query->select('*');
        $query->where('reserved_at_gmt', 'IS NOT', 'NULL');
        $query->orWhere(function($query) use ($itemData1, $itemData2) {
            $query->where('payload', serialize($itemData1));
            $query->orWhere('payload', serialize($itemData2));
        });
        $query->order('id', 'DESC');
        $this->assertEquals(2, $query->count());
        $queue->deleteItems([$item1, $item2]);
        $this->assertEquals(0, $query->count());
    }
}
