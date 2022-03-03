<?php

namespace Unit;

use Servebolt\Optimizer\Utils\DatabaseMigration\MigrationRunner;
use ServeboltWPUnitTestCase;

class DatabaseMigrationTest extends ServeboltWPUnitTestCase
{

    public function setUp(): void
    {
        parent::setUp();
        $this->allowPersistenceInDatabase();
        MigrationRunner::refresh();
    }

    public function tearDown(): void
    {
        parent::tearDown();
        MigrationRunner::cleanup();
        $this->disallowPersistenceInDatabase();
    }

    public function testThatTablesExists()
    {
        MigrationRunner::remigrate();
        $instance = new MigrationRunner;
        $this->assertTrue($instance->tablesExist());
    }

    public function testThatTablesDoesNotExists()
    {
        MigrationRunner::cleanup();
        $instance = new MigrationRunner;
        $this->assertFalse($instance->tablesExist());
    }

    public function testDownMigrationVersionConstraintMethod()
    {
        $this->assertTrue(MigrationRunner::eligibleForDownMigration('3.4.2-beta.1', '5.6', '3.4.2-beta.1'));
        $this->assertTrue(MigrationRunner::eligibleForDownMigration('3.4.2-beta.3', '3.4.2-beta.9', '3.4.2-beta'));
        $this->assertTrue(MigrationRunner::eligibleForDownMigration('3.4.2-beta.1', '5.6', '3.2.2-beta.1'));
        $this->assertFalse(MigrationRunner::eligibleForDownMigration('3.4.2-beta.1', '5.6', '3.4.2-beta.3'));
        $this->assertFalse(MigrationRunner::eligibleForDownMigration('3.4.2-beta.1', '5.6', '3.4.3'));
        $this->assertTrue(MigrationRunner::eligibleForDownMigration('3.2.2', '5.6', '3.2.1'));
        $this->assertTrue(MigrationRunner::eligibleForDownMigration('3.2', '5.6', '3.1'));
        $this->assertTrue(MigrationRunner::eligibleForDownMigration('3', '4', '2'));
        $this->assertFalse(MigrationRunner::eligibleForDownMigration('1', '4', '2'));
        $this->assertFalse(MigrationRunner::eligibleForDownMigration('3', '4', '4'));
        $this->assertFalse(MigrationRunner::eligibleForDownMigration('3', '5', '4'));
    }

    public function testUpMigrationVersionConstraintMethod()
    {
        $this->assertTrue(MigrationRunner::eligibleForUpMigration('2.0.1', '1.9.6', '2.0.1'));
        $this->assertTrue(MigrationRunner::eligibleForUpMigration('2.0.1', '1.9.6', '2.4.1'));
        $this->assertFalse(MigrationRunner::eligibleForUpMigration('2.0.1', '1.9.6', '2.0.0'));
        $this->assertFalse(MigrationRunner::eligibleForUpMigration('2.5-beta', '2.5-beta.2', '5.2.1'));
        $this->assertTrue(MigrationRunner::eligibleForUpMigration('2.5-beta.3', '2.5-beta.2', '5.2.1'));
        $this->assertTrue(MigrationRunner::eligibleForUpMigration('3.2.2', '3.2.1', '5.6'));
        $this->assertTrue(MigrationRunner::eligibleForUpMigration('3.2', '3.1', '5.6'));
        $this->assertTrue(MigrationRunner::eligibleForUpMigration('3', '2', '4'));
        $this->assertFalse(MigrationRunner::eligibleForUpMigration('1', '2', '4'));
        $this->assertFalse(MigrationRunner::eligibleForUpMigration('3', '4', '4'));
        $this->assertFalse(MigrationRunner::eligibleForUpMigration('3', '4', '5'));
    }
}
