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

    public function testThatMigrationsResultInDatabasesBeingCreated(): void
    {
        $this->instance->checkTables();
        $this->assertTrue($this->instance->tablesExist());
    }

    public function testThatMigrationsResultInDatabasesBeingDeleted(): void
    {
        $this->instance->deleteTables();
        $this->assertFalse($this->instance->tablesExist());
    }
}
