<?php

namespace Unit;

use Servebolt\Optimizer\CronControl\WpCronDisabler;
use ServeboltWPUnitTestCase;

class WpCronDisablerTest extends ServeboltWPUnitTestCase
{
    public function testThatWeCanControlWpCron()
    {
        add_filter('sb_optimizer_wp_config_path', function() {
            return __DIR__ . '/wp-config-sample.php';
        });
        WpCronDisabler::enableWpCron();

        $this->assertTrue(WpCronDisabler::wpCronIsEnabled());
        $this->assertFalse(WpCronDisabler::wpCronIsDisabled());

        WpCronDisabler::disableWpCron();
        $this->assertFalse(WpCronDisabler::wpCronIsEnabled());
        $this->assertTrue(WpCronDisabler::wpCronIsDisabled());

        WpCronDisabler::enableWpCron();
        $this->assertTrue(WpCronDisabler::wpCronIsEnabled());
        $this->assertFalse(WpCronDisabler::wpCronIsDisabled());

        remove_all_filters('sb_optimizer_wp_config_path');
    }
}
