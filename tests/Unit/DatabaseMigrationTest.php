<?php

namespace Unit;

use Servebolt\Optimizer\DatabaseMigration\MigrationRunner;
use ServeboltWPUnitTestCase;

class DatabaseMigrationTest extends ServeboltWPUnitTestCase
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

    public function testThatTablesExists()
    {
        MigrationRunner::migrate();
        $instance = new MigrationRunner;
        $this->assertTrue($instance->tablesExist());
    }

    public function testThatTablesDoesNotExists()
    {
        MigrationRunner::cleanup();
        $instance = new MigrationRunner;
        $this->assertFalse($instance->tablesExist());
    }
}
