<?php

namespace Unit\Prefetch;

use Servebolt\Optimizer\TextDomainLoader\WpTextDomainLoader;
use ServeboltWPUnitTestCase;

class TextDomainLoaderTest extends ServeboltWPUnitTestCase
{
    public function testThatCustomLoaderWorks()
    {
        $testLocale = 'es_ES';
        $testString = 'An example';
        $textDomain = 'sb-optimizer-test';
        $this->assertEquals($testString, __($testString, $textDomain));
        switch_to_locale($testLocale);
        $this->assertEquals($testString, __($testString, $textDomain));
        add_filter('sb_optimizer_get_option_servebolt_custom_text_domain_loader_switch', '__return_true');
        WpTextDomainLoader::init();
        $this->assertEquals(1, has_filter('override_load_textdomain', 'Servebolt\Optimizer\TextDomainLoader\WpTextDomainLoader::aFasterLoadTextDomain'));
        load_textdomain($textDomain, __DIR__ . '/test-' . $testLocale . '.mo');
        $this->assertEquals('Un ejemplo', __($testString, $textDomain));
    }
}
