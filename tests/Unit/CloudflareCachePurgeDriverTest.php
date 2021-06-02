<?php

namespace Unit;

use WP_UnitTestCase;
use Servebolt\Optimizer\CachePurge\Drivers\Cloudflare as CloudflareSdk;

class CloudflareCachePurgeDriverTest extends WP_UnitTestCase
{

    private $client;

    public function setUp()
    {
        parent::setUp();
        $this->client = CloudflareSdk::getInstance();
    }

    public function testThatSdkClientContainsPurgeMethods()
    {
        $this->assertTrue(method_exists($this->client, 'purgeByUrl'));
        $this->assertTrue(method_exists($this->client, 'purgeByUrls'));
        $this->assertTrue(method_exists($this->client, 'purgeAll'));
    }
}
