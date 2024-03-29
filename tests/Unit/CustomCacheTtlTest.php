<?php

namespace Unit;

use Servebolt\Optimizer\FullPageCache\CacheTtl;
use ServeboltWPUnitTestCase;
use Unit\Traits\HeaderTestTrait;
use function Servebolt\Optimizer\Helpers\deleteOption;
use function Servebolt\Optimizer\Helpers\getOption;
use function Servebolt\Optimizer\Helpers\updateOption;

class CustomCacheTtlTest extends ServeboltWPUnitTestCase
{
    use HeaderTestTrait;

    public function testThatNullIsReturnedForNonExistingPostTypes(): void
    {
        $this->assertNull(CacheTtl::getTtlByPostType('non-existing-post-type'));
    }

    public function testThatDefaultValuesAreSet(): void
    {
        $this->assertEquals(86400, CacheTtl::getTtlByPostType('post'));
    }

    public function testThatWeCanSetCustomTtlForPostType(): void
    {
        $customTtl = 69;
        $ttlPresets = getOption('cache_ttl_by_post_type');
        $ttlPresets['page'] = 'custom';
        updateOption('cache_ttl_by_post_type', $ttlPresets);

        $customTtlByPostTypes = getOption('custom_cache_ttl_by_post_type', []);
        $customTtlByPostTypes['page'] = $customTtl;
        updateOption('custom_cache_ttl_by_post_type', $customTtlByPostTypes);
        $this->assertEquals($customTtl, CacheTtl::getTtlByPostType('page'));

        $customTtlByPostTypes['page'] = 'a-string';
        updateOption('cache_ttl_by_post_type', $customTtlByPostTypes);
        $this->assertNull(CacheTtl::getTtlByPostType('page'));

        $ttlPresets = getOption('cache_ttl_by_post_type');
        $ttlPresets['page'] = 'default';
        updateOption('cache_ttl_by_post_type', $ttlPresets);
        $this->assertEquals(86400, CacheTtl::getTtlByPostType('page'));
        deleteOption('cache_ttl_by_post_type');
    }

    public function testAllCacheTtlPresets(): void
    {
        deleteOption('cache_ttl_by_post_type');
        $ttlPresets = getOption('cache_ttl_by_post_type');
        $ttlPresets['page'] = 'off';
        updateOption('cache_ttl_by_post_type', $ttlPresets);
        $this->assertEquals(0, CacheTtl::getTtlByPostType('page'));

        $ttlPresets['page'] = 'very-short';
        updateOption('cache_ttl_by_post_type', $ttlPresets);
        $this->assertEquals(600, CacheTtl::getTtlByPostType('page'));

        $ttlPresets['page'] = 'short';
        updateOption('cache_ttl_by_post_type', $ttlPresets);
        $this->assertEquals(43200, CacheTtl::getTtlByPostType('page'));

        $ttlPresets['page'] = 'default';
        updateOption('cache_ttl_by_post_type', $ttlPresets);
        $this->assertEquals(86400, CacheTtl::getTtlByPostType('page'));

        $ttlPresets['page'] = 'long';
        updateOption('cache_ttl_by_post_type', $ttlPresets);
        $this->assertEquals(1209600, CacheTtl::getTtlByPostType('page'));
        deleteOption('cache_ttl_by_post_type');
    }
}
