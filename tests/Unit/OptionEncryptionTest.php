<?php

namespace Unit;

use ServeboltWPUnitTestCase;
use function Servebolt\Optimizer\Helpers\deleteBlogOption;
use function Servebolt\Optimizer\Helpers\deleteOption;
use function Servebolt\Optimizer\Helpers\deleteSiteOption;
use function Servebolt\Optimizer\Helpers\getBlogOption;
use function Servebolt\Optimizer\Helpers\getOption;
use function Servebolt\Optimizer\Helpers\getSiteOption;
use function Servebolt\Optimizer\Helpers\updateBlogOption;
use function Servebolt\Optimizer\Helpers\updateOption;
use function Servebolt\Optimizer\Helpers\getOptionName;
use function Servebolt\Optimizer\Helpers\iterateSites;
use function Servebolt\Optimizer\Helpers\updateSiteOption;

class OptionEncryptionTest extends ServeboltWPUnitTestCase
{
    public function testMultisiteOptions()
    {
        $this->skipWithoutMultisite();
        iterateSites(function ($site) {
            $value = 'test-value-' . $site->blog_id;
            $key = 'sb-test-options-key';
            $this->assertFalse(getBlogOption($site->blog_id, $key));
            $this->assertTrue(updateBlogOption($site->blog_id, $key, $value));
            $this->assertNotEquals($value, get_blog_option($site->blog_id, getOptionName($key)));
            $this->assertEquals($value, getBlogOption($site->blog_id, $key));
            deleteBlogOption($site->blog_id, $key);
            $this->assertFalse(getBlogOption($site->blog_id, $key));
        }, true);
    }

    public function testSiteOptions()
    {
        $this->skipWithoutMultisite();
        $value = 'test-value';
        $key = 'sb-test-options-key';
        $this->assertFalse(getSiteOption($key));
        $this->assertTrue(updateSiteOption($key, $value));
        $this->assertNotEquals($value, get_site_option(getOptionName($key)));
        $this->assertEquals($value, getSiteOption($key));
        deleteSiteOption($key);
        $this->assertFalse(getSiteOption($key));
    }

    public function testSingleSiteOptions()
    {
        $value = 'test-value';
        $key = 'sb-test-options-key';
        $this->assertFalse(getOption($key));
        $this->assertTrue(updateOption($key, $value));
        $this->assertNotEquals($value, get_option(getOptionName($key)));
        $this->assertEquals($value, getOption($key));
        deleteOption($key);
        $this->assertFalse(getOption($key));
    }
}
