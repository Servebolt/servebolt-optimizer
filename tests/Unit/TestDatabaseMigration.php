<?php

namespace Unit;

class TestDatabaseMigration extends WP_UnitTestCase
{

    public function testThatMigrations(): void
    {
        define('SB_DEBUG', true);
        $this->assertEquals(getServeboltAdminUrl(), 'https://admin.servebolt.com/siteredirect/?site=4321');
    }
}
