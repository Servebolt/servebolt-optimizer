<?php

namespace Unit;

use Servebolt\Optimizer\Utils\DatabaseMigration\MigrationRunner;
use Servebolt\Optimizer\Utils\Queue\Queue;
use Servebolt\Optimizer\Utils\SqlBuilder\SqlBuilder;
use Servebolt\Optimizer\Utils\SqlBuilder\WpSqlBuilder;
use ServeboltWPUnitTestCase;

/**
 * Class SqlBuilderTest
 * @package Unit\Queue
 */
class SqlBuilderTest extends ServeboltWPUnitTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->allowPersistenceInDatabase();
        MigrationRunner::migrateFresh();
    }

    public function tearDown(): void
    {
        parent::tearDown();
        MigrationRunner::cleanup();
        $this->disallowPersistenceInDatabase();
    }

    public function testSqlBuilderSelect()
    {
        $query = SqlBuilder::query('sb_queue')
            ->selectCount()
            ->from('sb_queue')
            ->order('id', 'ASC')
            ->where('reserved_at_gmt', 'IS NOT', 'NULL')
            ->orWhere(function($query) {
                $query->where('payload', '1')
                    ->orWhere(function($query) {
                        $query->where('payload', '2')
                            ->orWhere('payload', '3');
                    });
            });
        $sql = $query->buildQuery();
        $this->assertIsString($sql);
        $this->assertEquals("SELECT COUNT(*) FROM `sb_queue` WHERE `reserved_at_gmt` IS NOT NULL OR (`payload` = '1' OR (`payload` = '2' OR `payload` = '3')) ORDER BY id ASC", $sql);
    }

    public function testSqlBuilderDelete()
    {
        $query = SqlBuilder::query('sb_queue')
            ->delete()
            ->from('sb_queue')
            ->where('reserved_at_gmt', 'IS NOT', 'NULL')
            ->orWhere(function($query) {
                $query->where('payload', '1')
                    ->orWhere(function($query) {
                        $query->where('payload', '2')
                            ->orWhere('payload', '3');
                    });
            });
        $sql = $query->buildQuery();
        $this->assertIsString($sql);
        $this->assertEquals("DELETE FROM `sb_queue` WHERE `reserved_at_gmt` IS NOT NULL OR (`payload` = '1' OR (`payload` = '2' OR `payload` = '3'))", $sql);
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

        $query = WpSqlBuilder::query('sb_queue')
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

        $query = WpSqlBuilder::query('sb_queue')
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
