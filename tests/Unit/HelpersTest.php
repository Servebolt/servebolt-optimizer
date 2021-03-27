<?php

namespace Unit;

use WP_UnitTestCase;
use function Servebolt\Optimizer\Helpers\getServeboltAdminUrl;

class HelpersTest extends WP_UnitTestCase
{

    public function testThatWeCanGetAdminUrlFromHomePath(): void
    {
        define('SB_DEBUG', true);
        $this->assertEquals(getServeboltAdminUrl(), 'https://admin.servebolt.com/siteredirect/?site=4321');
    }

    public function testThatTestConstantGetsSet()
    {
        $this->assertTrue(defined('WP_TESTS_IS_RUNNING'));
        $this->assertTrue(WP_TESTS_IS_RUNNING);
    }
}
