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
        // TODO: Set language to nb_NO
        $this->assertEquals('This is a test', __('This is a test', 'sb-optimizer-test'));
        add_filter('sb_optimizer_get_option_servebolt_custom_text_domain_loader_switch', '__return_true');
        new WpTextDomainLoader;
        // TODO: Load MO-file
        $this->assertEquals('Dette er en test', __('This is a test', 'sb-optimizer-test'));
    }
}
