<?php

namespace Unit;

use WP_UnitTestCase;
use Servebolt\Optimizer\CachePurge\Drivers\Servebolt as ServeboltSdk;

class ServeboltCachePurgeDriverTest extends WP_UnitTestCase
{

    private $client;

    public function setUp()
    {
        parent::setUp();
        $this->client = ServeboltSdk::getInstance();
    }

    public function testThatSdkClientContainsPurgeMethods()
    {
        var_dump($this->client);
        //var_dump($this->client->getPurgeAllPrefixes());
        die;

        /*
        $this->assertTrue(method_exists($this->client, 'purgeByUrl'));
        $this->assertTrue(method_exists($this->client, 'purgeByUrls'));
        $this->assertTrue(method_exists($this->client, 'purgeAll'));
        */

        $this->assertEquals();
    }
}
