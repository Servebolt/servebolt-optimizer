<?php

namespace Unit\Traits;

use Servebolt\Optimizer\FullPageCache\FullPageCacheHeaders;

/**
 * Trait HeaderTestTrait
 * @package Unit\Traits
 */
trait HeaderTestTrait
{
    private function setupHeaderTest()
    {
        add_filter('sb_optimizer_fpc_should_debug_headers', '__return_true');
        FullPageCacheHeaders::destroyInstance();
        $instance = FullPageCacheHeaders::getInstance();
        FullPageCacheHeaders::mock();
        $instance->shouldSetCacheHeaders(false);
        return $instance;
    }
}
