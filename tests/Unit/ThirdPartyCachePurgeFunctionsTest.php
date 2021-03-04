<?php

namespace Unit;

use WP_UnitTestCase;

class ThirdPartyCachePurgeFunctionsTest extends WP_UnitTestCase
{
    public function testThatThirdPartyCachePurgeFunctionsExists()
    {
        $this->assertTrue(function_exists('sb_purge_post_cache'));
        $this->assertTrue(function_exists('sb_purge_post_term'));
        $this->assertTrue(function_exists('sb_purge_all'));
    }
}
