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

    public function testThatCountWorks()
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
        $queue->reserveItem($item2);

        $query = SqlBuilder::query('sb_queue')
            ->selectCount()
            ->from('sb_queue')
            ->order('id', 'ASC')
            ->where('reserved_at_gmt', 'IS NOT', 'NULL')
            ->orWhere(function($query) use ($itemData1, $itemData2) {
                $query->where('payload', serialize($itemData1))
                    ->orWhere(function($query) use ($itemData1, $itemData2) {
                        $query->where('payload', serialize($itemData1))
                            ->orWhere('payload', serialize($itemData2));
                    });
            });
        $this->assertEquals(2, $query->result());
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
            'foo2' => 'bar',
            'bar2' => 'foo',
        ];
        $item2 = $queue->add($itemData2);
        $queue->reserveItem($item2);

        $query = SqlBuilder::query('sb_queue')
        ->select('*')
        ->from('sb_queue')
        ->order('id', 'ASC')
        ->where('reserved_at_gmt', 'IS NOT', 'NULL')
        ->orWhere(function($query) use ($itemData1, $itemData2) {
            $query->where('payload', serialize($itemData1))
            ->orWhere(function($query) use ($itemData1, $itemData2) {
                $query->where('payload', serialize($itemData1))
                    ->orWhere('payload', serialize($itemData2));
            });
        });

        $this->assertEquals(serialize($itemData1), $query->first()->payload);
        $query->order('id', 'DESC');
        $this->assertEquals(serialize($itemData2), $query->first()->payload);
        $query->removeOrder();
        $this->assertEquals(serialize($itemData1), $query->first()->payload);

        $this->assertEquals(2, $query->count());

        $query->limit(1);
        $this->assertEquals(1, $query->count());

        $query->removeLimit();
        $this->assertEquals(2, $query->count());

        $queue->deleteItems([$item1, $item2]);
        $this->assertEquals(0, $query->count());
    }
}
