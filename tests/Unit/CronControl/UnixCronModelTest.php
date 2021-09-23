<?php

namespace Unix;

use Servebolt\Optimizer\CronControl\Commands\WpCliEventRun;
use Servebolt\Optimizer\CronControl\UnixCronModel;
use WP_UnitTestCase;

class UnixCronModelTest extends WP_UnitTestCase
{
    public function testThatSomething()
    {
        $this->assertTrue(UnixCronModel::exists(new WpCliEventRun));
    }
}
