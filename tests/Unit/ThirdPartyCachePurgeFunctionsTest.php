<?php

namespace Unit;

use WP_UnitTestCase;

class ThirdPartyCachePurgeFunctionsTest extends WP_UnitTestCase
{
    public function testThatThirdPartyCachePurgeFunctionsExists()
    {
        $this->assertTrue(function_exists('sb_purge_url'));
        $this->assertTrue(function_exists('sb_purge_post_cache'));
        $this->assertTrue(function_exists('sb_purge_term_cache'));
        $this->assertTrue(function_exists('sb_purge_all'));
        $this->assertTrue(function_exists('sb_purge_all_by_blog_id'));
        $this->assertTrue(function_exists('sb_purge_all_network'));
    }
}
