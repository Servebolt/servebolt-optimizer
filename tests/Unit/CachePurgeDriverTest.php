<?php

namespace Unit;

use WP_UnitTestCase;
use Servebolt\Optimizer\CachePurge\CachePurge;

class CachePurgeDriverTest extends WP_UnitTestCase
{
    private $driver;

    public function setUp() : void
    {
        parent::setUp();
        $this->driver = CachePurge::getInstance();
    }

    public function testThatCachePurgeDriverContainsPurgeMethods()
    {
        if ($this->driver->getDriverObject()) { // Check if we can resolve the cache purge driver
            $this->assertTrue(method_exists($this->driver->getDriverObject(), 'purgeByUrl'));
            $this->assertTrue(method_exists($this->driver->getDriverObject(), 'purgeByUrls'));
            $this->assertTrue(method_exists($this->driver->getDriverObject(), 'purgeAll'));
        } else {
            $this->assertNull($this->driver->getDriverObject()); // No driver available since cache purge is not configured
        }

    }
}
