<?php

namespace Unit;

use ServeboltWPUnitTestCase;
use Servebolt\Optimizer\Database\PluginTables;

/**
 * Class PluginTablesTest
 * @package Unit
 */
class PluginTablesTest extends ServeboltWPUnitTestCase
{
    private $instance;

    public function testThatMigrationsResultInDatabasesBeingCreatedAndDeleted(): void
    {
        $this->assertTrue($this->instance->checkTable('queue'));
        $this->assertTrue($this->instance->tableExists('queue'));
        $this->assertTrue($this->instance->deleteTable('queue'));
        $this->assertFalse($this->instance->tableExists('queue'));
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->cleanup();
        $this->disallowPersistenceInDatabase();
    }

    public function setUp(): void
    {
        parent::setUp();
        $this->instance = new PluginTables(false);
        $this->allowPersistenceInDatabase();
        $this->cleanup();
    }

    private function cleanup(): void
    {
        if ($this->instance->tablesExist()) {
            $this->instance->deleteTables();
        }
    }
}
