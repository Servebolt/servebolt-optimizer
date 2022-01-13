<?php

namespace Unit;

use ServeboltWPUnitTestCase;
use Unit\Traits\MultisiteTrait;
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
    use MultisiteTrait;

    public function testMultisiteOptions()
    {
        $this->skipWithoutMultisite();
        $this->createBlogs(3);
        iterateSites(function ($site) {
            $value = 'test-value-' . $site->blog_id;
            $key = 'sb-test-options-key';
            $this->assertNull(getBlogOption($site->blog_id, $key));
            $this->assertTrue(updateBlogOption($site->blog_id, $key, $value));
            $this->assertNotEquals($value, get_blog_option($site->blog_id, getOptionName($key)));
            $this->assertEquals($value, getBlogOption($site->blog_id, $key));
            deleteBlogOption($site->blog_id, $key);
            $this->assertNull(getBlogOption($site->blog_id, $key));
        }, true);
        $this->deleteBlogs();
    }

    public function testSiteOptions()
    {
        $this->skipWithoutMultisite();
        $value = 'test-value';
        $key = 'sb-test-options-key';
        $this->assertNull(getSiteOption($key));
        $this->assertTrue(updateSiteOption($key, $value));
        $this->assertNotEquals($value, get_site_option(getOptionName($key)));
        $this->assertEquals($value, getSiteOption($key));
        deleteSiteOption($key);
        $this->assertNull(getSiteOption($key));
    }

    public function testSingleSiteOptions()
    {
        $value = 'test-value';
        $key = 'sb-test-options-key';
        $this->assertNull(getOption($key));
        $this->assertTrue(updateOption($key, $value));
        $this->assertNotEquals($value, get_option(getOptionName($key)));
        $this->assertEquals($value, getOption($key));
        deleteOption($key);
        $this->assertNull(getOption($key));
    }
}
