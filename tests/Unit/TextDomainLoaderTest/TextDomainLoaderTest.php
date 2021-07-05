<?php

namespace Unit\Prefetch;

use Servebolt\Optimizer\TextDomainLoader\WpTextDomainLoader;
use Unit\Traits\MultisiteTrait;
use ServeboltWPUnitTestCase;

class TextDomainLoaderTest extends ServeboltWPUnitTestCase
{
    use MultisiteTrait;

    public function testThatCustomLoaderWorks()
    {
        $textDomain = 'sb-optimizer-test';
        $this->assertEquals('An example', __('An example', $textDomain));
        switch_to_locale('es_ES');
        $this->assertEquals('An example', __('An example', $textDomain));
        add_filter('sb_optimizer_get_option_servebolt_custom_text_domain_loader_switch', '__return_true');
        new WpTextDomainLoader;
        $this->assertEquals(1, has_filter('override_load_textdomain', 'Servebolt\Optimizer\TextDomainLoader\WpTextDomainLoader::aFasterLoadTextDomain'));
        load_textdomain($textDomain, __DIR__ . '/test-es_ES.mo');
        $this->assertEquals('Un ejemplo', __('An example', $textDomain));
    }
}
