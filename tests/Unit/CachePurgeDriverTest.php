<?php

namespace Unit;

use WP_UnitTestCase;
use Servebolt\Optimizer\CachePurge\CachePurge;

class CachePurgeDriverTest extends WP_UnitTestCase
{
    private $client;

    public function setUp()
    {
        parent::setUp();
        $this->client = CachePurge::getInstance();
    }

    public function testThatCachePurgeDriverContainsPurgeMethods()
    {
        $this->assertTrue(true);
        // TODO: Add tests to check that we get the correct driver etc.
    }
}
