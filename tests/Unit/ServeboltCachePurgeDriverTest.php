<?php

namespace Unit;

use WP_UnitTestCase;
use Servebolt\Optimizer\Api\CachePurge\Drivers\Servebolt;

class ServeboltCachePurgeDriverTest extends WP_UnitTestCase
{
    public function testServeboltCachePurgeDriverInit()
    {
        $client = Servebolt::getInstance();
        $this->assertTrue(true);
    }
}
