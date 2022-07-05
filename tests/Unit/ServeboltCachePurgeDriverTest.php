<?php

namespace Unit;

use WP_UnitTestCase;
use Servebolt\Optimizer\CachePurge\Drivers\Servebolt as ServeboltSdk;

class ServeboltCachePurgeDriverTest extends WP_UnitTestCase
{

    private $client;

    public function setUp() : void
    {
        parent::setUp();
        $this->client = ServeboltSdk::getInstance();
    }

    public function testThatSdkClientContainsPurgeMethods()
    {
        $this->assertTrue(method_exists($this->client, 'purgeByUrl'));
        $this->assertTrue(method_exists($this->client, 'purgeByUrls'));
        $this->assertTrue(method_exists($this->client, 'purgeAll'));
    }

    public function testThatPurgeAllPrefixesAreCorrect()
    {
        $this->assertEquals($this->client->getPurgeAllPrefixes(), ['example.org', 'www.example.org']);
    }
}
