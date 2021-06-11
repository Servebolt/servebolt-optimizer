<?php

namespace Unit;

use WP_UnitTestCase;
use Servebolt\Optimizer\AcceleratedDomains\AcceleratedDomains;

class AcceleratedDomainsTest extends WP_UnitTestCase
{
    public function setUp()
    {
        parent::setUp();
        AcceleratedDomains::init();
    }

    public function testThatAcdActiveStateActionsAreTriggered()
    {
        $this->assertEquals(0, did_action('sb_optimizer_acd_enable'));
        $this->assertEquals(0, did_action('sb_optimizer_acd_disable'));
        AcceleratedDomains::toggleActive(true);
        $this->assertEquals(1, did_action('sb_optimizer_acd_enable'));
        $this->assertEquals(0, did_action('sb_optimizer_acd_disable'));
        AcceleratedDomains::toggleActive(false);
        $this->assertEquals(1, did_action('sb_optimizer_acd_disable'));
        AcceleratedDomains::toggleActive(true);
        $this->assertEquals(2, did_action('sb_optimizer_acd_enable'));
        AcceleratedDomains::toggleActive(false);
        $this->assertEquals(2, did_action('sb_optimizer_acd_disable'));
    }
}
