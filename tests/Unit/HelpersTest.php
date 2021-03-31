<?php

namespace Unit;

use Servebolt\Optimizer\Admin\CloudflareImageResize\CloudflareImageResize;
use Servebolt\Optimizer\Queue\QueueSystem\QueueItem;
use WP_UnitTestCase;
use function Servebolt\Optimizer\Helpers\arrayGet;
use function Servebolt\Optimizer\Helpers\booleanToStateString;
use function Servebolt\Optimizer\Helpers\booleanToString;
use function Servebolt\Optimizer\Helpers\camelCaseToSnakeCase;
use function Servebolt\Optimizer\Helpers\checkboxIsChecked;
use function Servebolt\Optimizer\Helpers\countSites;
use function Servebolt\Optimizer\Helpers\displayValue;
use function Servebolt\Optimizer\Helpers\featureIsActive;
use function Servebolt\Optimizer\Helpers\featureIsAvailable;
use function Servebolt\Optimizer\Helpers\formatArrayToCsv;
use function Servebolt\Optimizer\Helpers\formatCommaStringToArray;
use function Servebolt\Optimizer\Helpers\formatPostTypeSlug;
use function Servebolt\Optimizer\Helpers\generateRandomPermanentKey;
use function Servebolt\Optimizer\Helpers\generateRandomString;
use function Servebolt\Optimizer\Helpers\getAjaxNonce;
use function Servebolt\Optimizer\Helpers\getAjaxNonceKey;
use function Servebolt\Optimizer\Helpers\getPostTitleByBlog;
use function Servebolt\Optimizer\Helpers\getServeboltAdminUrl;
use function Servebolt\Optimizer\Helpers\isAjax;
use function Servebolt\Optimizer\Helpers\isCli;
use function Servebolt\Optimizer\Helpers\isCron;
use function Servebolt\Optimizer\Helpers\isHostedAtServebolt;
use function Servebolt\Optimizer\Helpers\isQueueItem;
use function Servebolt\Optimizer\Helpers\isTesting;
use function Servebolt\Optimizer\Helpers\isWpRest;
use function Servebolt\Optimizer\Helpers\naturalLanguageJoin;
use function Servebolt\Optimizer\Helpers\resolveViewPath;
use function Servebolt\Optimizer\Helpers\isUrl;
use function Servebolt\Optimizer\Helpers\getOptionName;

class HelpersTest extends WP_UnitTestCase
{

    public function testThatWeCanGetAdminUrlFromHomePath(): void
    {
        define('SB_DEBUG', true);
        $this->assertEquals(getServeboltAdminUrl(), 'https://admin.servebolt.com/siteredirect/?site=4321');
    }

    public function testThatTestConstantGetsSet(): void
    {
        $this->assertTrue(defined('WP_TESTS_IS_RUNNING'));
        $this->assertTrue(WP_TESTS_IS_RUNNING);
    }

    public function testThatViewCanBeResolved(): void
    {
        $this->assertIsString(resolveViewPath('log-viewer.log-viewer'));
    }

    public function testThatUrlValidationWorks(): void
    {
        $this->assertTrue(isUrl('http://some-url.com/some-path?some-argument=some-value#some-hashtag'));
    }

    public function testThatOptionNameIsCorrect()
    {
        $this->assertEquals('servebolt_some_option', getOptionName('some_option'));
    }

    public function testRandomStringGenerator()
    {
        $this->assertEquals(20, mb_strlen(generateRandomString(20)));
    }

    public function testDisplayValueHelper()
    {
        $this->assertEquals('true', displayValue(true, true));
        $this->assertEquals('false', displayValue(false, true));
        $this->assertEquals('string', displayValue('string', true));
    }

    public function testCamelCaseToSnakeCase()
    {
        $this->assertEquals('some_method_with_camel_case', camelCaseToSnakeCase('someMethodWithCamelCase'));
    }

    public function testIsQueueItemHelper()
    {
        $item = new QueueItem((object)[
            'parent_id' => 1,
            'parent_queue_name' => 'some-parent-queue',
            'queue' => 'some-queue',
            'payload' => '',
            'attempts' => 0,
            'force_retry' => false,
            'failed_at_gmt' => null,
            'reserved_at_gmt' => null,
            'completed_at_gmt' => null,
            'updated_at_gmt' => null,
            'created_at_gmt' => null,
        ]);
        $this->assertTrue(isQueueItem($item));
    }

    public function testIsCliHelper()
    {
        $this->assertFalse(isCli());
        define('WP_CLI', true);
        $this->assertTrue(isCli());
    }

    public function testIsTestingHelper()
    {
        $this->assertTrue(isTesting());
    }

    public function testIsWpRestHelper()
    {
        $this->assertFalse(isWpRest());
    }

    public function testIsCronHelper()
    {

        $this->assertFalse(isCron());
    }

    public function testIsAjaxHelper()
    {
        $this->assertFalse(isAjax());
    }

    public function testGetAjaxNonceHelper()
    {
        $this->assertIsString(getAjaxNonce());
        $this->assertIsString(getAjaxNonceKey());
    }

    public function testGenerateRandomPermanentKey()
    {
        $string = generateRandomPermanentKey('some-permanent-string');
        $this->assertIsString($string);
        $this->assertEquals($string, generateRandomPermanentKey('some-permanent-string'));
    }

    public function testArrayGetHelper()
    {
        $array = [
            'some' => 'data',
            'other' => 'info',
            'third' => 'thing',
        ];
        $this->assertEquals('thing', arrayGet('third', $array));
    }

    public function testFormatCommaStringToArrayHelper()
    {
        $rootArray = ['some', 'string', 'with', 'values'];
        $array = formatCommaStringToArray(implode(',', $rootArray));
        $this->assertEquals($rootArray, $array);
    }

    public function testBooleanToStateStringHelper()
    {
        $this->assertEquals('active', booleanToStateString(true));
        $this->assertEquals('inactive', booleanToStateString(false));
    }

    public function testCheckboxIsChecked()
    {
        $this->assertTrue(checkboxIsChecked('on'));
        $this->assertTrue(checkboxIsChecked('yes', 'yes'));
        $this->assertFalse(checkboxIsChecked('off'));
        $this->assertFalse(checkboxIsChecked('no'));
    }

    public function testBooleanToStringHelper()
    {
        $this->assertEquals('true', booleanToString(true));
        $this->assertEquals('false', booleanToString(false));
    }

    public function testFormatPostTypeSlugHelper()
    {
        $this->assertEquals('Some slug of a post', formatPostTypeSlug('some-slug-of-a-post'));
    }

    public function testFeatureIsAvailableHelper()
    {
        $this->assertNull(featureIsAvailable('non-existing-feature'));
        $this->assertTrue(featureIsAvailable('cf_image_resize'));
    }

    public function testFeatureIsActiveHelper()
    {
        CloudflareImageResize::toggleActive(true);
        $this->assertTrue(featureIsActive('cf_image_resize'));
        CloudflareImageResize::toggleActive(false);
        $this->assertFalse(featureIsActive('cf_image_resize'));
        $this->assertNull(featureIsActive('non-existing-feature'));
    }

    public function testFormatArrayToCsv()
    {
        $array = ['array', 'with', 'some', 'values'];
        $this->assertEquals('array,with,some,values', formatArrayToCsv($array));
    }

    public function testNaturalLanguageJoinHelper()
    {
        $this->assertEquals('"Something" and "Something"', naturalLanguageJoin(['Something', 'Something']));
        $this->assertEquals('Something and Something', naturalLanguageJoin(['Something', 'Something'], null, ''));
        $this->assertEquals('Something or Something', naturalLanguageJoin(['Something', 'Something'], 'or', ''));
        $this->assertEquals('Something, Something or Another thing', naturalLanguageJoin(['Something', 'Something', 'Another thing'], 'or', ''));
        $this->assertEquals("'Something', 'something' and 'another thing'", naturalLanguageJoin(['Something', 'something', 'another thing'], null, "'"));
    }

    public function testIsHostedAtServeboltHelper()
    {
        $this->assertFalse(isHostedAtServebolt());
        define('HOST_IS_SERVEBOLT_OVERRIDE', true);
        $this->assertTrue(isHostedAtServebolt());
    }

    public function testCountSitesHelper()
    {
        $this->assertEquals(1, countSites());
    }
}
