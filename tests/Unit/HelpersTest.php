<?php

namespace Unit;

use Servebolt\Optimizer\Admin\CloudflareImageResize\CloudflareImageResize;
use Servebolt\Optimizer\Utils\Queue\QueueItem;
use ServeboltWPUnitTestCase;
use function Servebolt\Optimizer\Helpers\arrayGet;
use function Servebolt\Optimizer\Helpers\booleanToStateString;
use function Servebolt\Optimizer\Helpers\booleanToString;
use function Servebolt\Optimizer\Helpers\camelCaseToSnakeCase;
use function Servebolt\Optimizer\Helpers\deleteAllSettings;
use function Servebolt\Optimizer\Helpers\deleteBlogOption;
use function Servebolt\Optimizer\Helpers\deleteOption;
use function Servebolt\Optimizer\Helpers\deleteSiteOption;
use function Servebolt\Optimizer\Helpers\getAllOptionsNames;
use function Servebolt\Optimizer\Helpers\getBlogOption;
use function Servebolt\Optimizer\Helpers\getCurrentPluginVersion;
use function Servebolt\Optimizer\Helpers\getOption;
use function Servebolt\Optimizer\Helpers\getSiteOption;
use function Servebolt\Optimizer\Helpers\iterateSites;
use function Servebolt\Optimizer\Helpers\smartDeleteOption;
use function Servebolt\Optimizer\Helpers\smartGetOption;
use function Servebolt\Optimizer\Helpers\smartUpdateOption;
use function Servebolt\Optimizer\Helpers\snakeCaseToCamelCase;
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
use function Servebolt\Optimizer\Helpers\strEndsWith;
use function Servebolt\Optimizer\Helpers\updateBlogOption;
use function Servebolt\Optimizer\Helpers\updateOption;
use function Servebolt\Optimizer\Helpers\updateSiteOption;

class HelpersTest extends ServeboltWPUnitTestCase
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
        $this->assertEquals('true', displayValue(true));
        $this->assertEquals('false', displayValue(false));
        $this->assertEquals('string', displayValue('string'));
    }

    public function testCamelCaseToSnakeCase()
    {
        $this->assertEquals('some_method_with_camel_case', camelCaseToSnakeCase('someMethodWithCamelCase'));
    }

    public function testSnakeCaseToCamelCase()
    {
        $this->assertEquals('someMethodWithCamelCase', snakeCaseToCamelCase('some_method_with_camel_case'));
        $this->assertEquals('SomeMethodWithCamelCase', snakeCaseToCamelCase('some_method_with_camel_case', true));
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

    public function testThatStringEndsWith()
    {
        $this->assertTrue(strEndsWith('some-long-string', 'string', false));
        $this->assertFalse(strEndsWith('some-long-string', 'string2', false));
        $this->assertTrue(strEndsWith('some-long-string', 'string', true));
        $this->assertFalse(strEndsWith('some-long-string', 'string2', true));
    }

    public function testIsHostedAtServeboltHelper()
    {
        $this->assertFalse(isHostedAtServebolt());
        add_filter('sb_optimizer_is_hosted_at_servebolt', '__return_true');
        $this->assertTrue(isHostedAtServebolt());
        remove_filter('sb_optimizer_is_hosted_at_servebolt', '__return_true');
        $this->assertFalse(isHostedAtServebolt());
        $_SERVER['SERVER_ADMIN'] = 'support@servebolt.comz';
        $this->assertFalse(isHostedAtServebolt());
        $_SERVER['SERVER_ADMIN'] = 'support@servebolt.com';
        $this->assertTrue(isHostedAtServebolt());
        unset($_SERVER['SERVER_ADMIN']);
        $this->assertFalse(isHostedAtServebolt());
        $_SERVER['HOSTNAME'] = 'accele-13661.bolt53.servebolt.comz';
        $this->assertFalse(isHostedAtServebolt());
        $_SERVER['HOSTNAME'] = 'accele-13661.bolt53.servebolt.com';
        $this->assertTrue(isHostedAtServebolt());
        $_SERVER['HOSTNAME'] = 'sbopti-7393.wilhelm-osl.servebolt.cloudz';
        $this->assertFalse(isHostedAtServebolt());
        $_SERVER['HOSTNAME'] = 'sbopti-7393.wilhelm-osl.servebolt.cloud';
        $this->assertTrue(isHostedAtServebolt());
        unset($_SERVER['HOSTNAME']);
        $this->assertFalse(isHostedAtServebolt());
        define('HOST_IS_SERVEBOLT_OVERRIDE', true);
        $this->assertTrue(isHostedAtServebolt());
    }

    public function testThatWeCanObtainPluginVersion()
    {
        $versionNumber = getCurrentPluginVersion(false);
        $this->assertIsString($versionNumber);
        $this->assertRegExp('/^([0-9]+)\.([0-9]+)\.([0-9]+)(?:-([0-9A-Za-z-]+(?:\.[0-9A-Za-z-]+)*))?(?:\+[0-9A-Za-z-]+)?$/', $versionNumber);
    }

    public function testCountSitesHelper()
    {
        $this->multisiteOnly();
        $this->assertEquals(1, countSites());
        $this->createBlogs(3);
        $this->assertEquals(4, countSites());
    }

    public function testSmartOptionsHelpersMultisite()
    {
        $this->multisiteOnly();
        $this->createBlogs(3);
        iterateSites(function ($site) {
            $key = 'some-option-for-testing';
            $this->assertNull(smartGetOption($site->blog_id, $key));
            $this->assertTrue(smartUpdateOption($site->blog_id, $key, 'some-value'));
            $this->assertEquals('some-value', smartGetOption($site->blog_id, $key));
            $this->assertTrue(smartDeleteOption($site->blog_id, $key));
            $this->assertNull(smartGetOption($site->blog_id, $key));
        }, true);
    }

    public function testOptionsOverride()
    {
        $override = function($value) {
            return 'override';
        };
        $key = 'some-overrideable-options-key';
        $fullKey = 'servebolt_' . $key;
        $this->assertEquals('some-default-value', getOption($key, 'some-default-value'));
        add_filter('sb_optimizer_get_option_' . $fullKey, $override);
        $this->assertEquals('override', getOption($key));
        $this->assertNotEquals('some-default-value', getOption($key, 'some-default-value'));

        updateOption($key, 'a-value');
        $this->assertEquals('override', getOption($key));
        $this->assertNotEquals('a-value', getOption($key, 'some-default-value'));

        remove_filter('sb_optimizer_get_option_' . $fullKey, $override);
        $this->assertEquals('a-value', getOption($key));
    }

    public function testBlogOptionsOverride()
    {
        $this->multisiteOnly();
        $this->createBlogs(2);
        iterateSites(function ($site) {
            $override = function ($value) {
                return 'override';
            };
            $key = 'some-overrideable-blog-options-key';
            $fullKey = 'servebolt_' . $key;
            $this->assertEquals('some-default-value', getBlogOption($site->blog_id, $key, 'some-default-value'));
            add_filter('sb_optimizer_get_blog_option_' . $fullKey, $override);
            $this->assertEquals('override', getBlogOption($site->blog_id, $key));
            $this->assertNotEquals('some-default-value', getBlogOption($site->blog_id, $key, 'some-default-value'));

            updateBlogOption($site->blog_id, $key, 'a-value');
            $this->assertEquals('override', getBlogOption($site->blog_id, $key));
            $this->assertNotEquals('a-value', getBlogOption($site->blog_id, $key, 'some-default-value'));

            remove_filter('sb_optimizer_get_blog_option_' . $fullKey, $override);
            $this->assertEquals('a-value', getBlogOption($site->blog_id, $key));
        }, true);
    }

    public function testDefaultValuesForOptionsHelpers()
    {
        $key = 'default-options-value-test-key';
        $this->assertEquals('default-value', getOption($key, 'default-value'));
        updateOption($key, 'an-actual-value');
        $this->assertNotEquals('default-value', getOption($key, 'default-value'));
        $this->assertEquals('an-actual-value', getOption($key, 'default-value'));
    }

    public function testDefaultValuesForSiteOptionsHelpers()
    {
        $key = 'default-options-value-test-key';
        $this->assertEquals('default-value', getSiteOption($key, 'default-value'));
        updateSiteOption($key, 'an-actual-value');
        $this->assertNotEquals('default-value', getSiteOption($key, 'default-value'));
        $this->assertEquals('an-actual-value', getSiteOption($key, 'default-value'));
    }

    public function testDefaultValuesForBlogOptionsHelpers()
    {
        $this->multisiteOnly();
        $this->createBlogs(3);
        iterateSites(function ($site) {
            $key = 'default-options-value-test-key';
            $this->assertEquals('default-value', getBlogOption($site->blog_id, $key, 'default-value'));
            updateBlogOption($site->blog_id, $key, 'an-actual-value');
            $this->assertNotEquals('default-value', getBlogOption($site->blog_id, $key, 'default-value'));
            $this->assertEquals('an-actual-value', getBlogOption($site->blog_id, $key, 'default-value'));
        }, true);
    }

    public function testDefaultValuesForSmartOptionsHelpers()
    {
        $key = 'default-options-value-test-key';
        $this->assertEquals('default-value', smartGetOption(null, $key, 'default-value'));
        smartUpdateOption(null, $key, 'an-actual-value');
        $this->assertNotEquals('default-value', smartGetOption(null, $key, 'default-value'));
        $this->assertEquals('an-actual-value', smartGetOption(null, $key, 'default-value'));
    }

    public function testSiteOptionsHelpers()
    {
        $key = 'some-option-for-testing-site-wide';
        $this->assertNull(getSiteOption($key));
        $this->assertTrue(updateSiteOption($key, 'some-value'));
        $this->assertEquals('some-value', getSiteOption($key));
        $this->assertTrue(deleteSiteOption($key));
        $this->assertNotEquals('some-value', getSiteOption($key));
        $this->assertNull(getSiteOption($key));
    }

    public function testOptionsHelpers()
    {
        $key = 'some-option-for-testing-single-site-option';
        $this->assertNull(getOption($key));
        $this->assertTrue(updateOption($key, 'some-value'));
        $this->assertEquals('some-value', getOption($key));
        $this->assertTrue(deleteOption($key));
        $this->assertNull(getOption($key));
    }

    public function testOptionsHelpersMultisite()
    {
        $this->multisiteOnly();
        $this->createBlogs(2);
        iterateSites(function ($site) {
            $key = 'some-option-for-testing-multisite';
            $this->assertNull(getBlogOption($site->blog_id, $key));
            $this->assertTrue(updateBlogOption($site->blog_id, $key, 'some-value'));
            $this->assertEquals('some-value', getBlogOption($site->blog_id, $key));
            $this->assertTrue(deleteBlogOption($site->blog_id, $key));
            $this->assertNull(getBlogOption($site->blog_id, $key));
        }, true);
    }

    public function testSmartOptionsHelpers()
    {
        $key = 'some-option-for-testing-smart-option';
        $this->assertNull(smartGetOption(null, $key));
        $this->assertTrue(smartUpdateOption(null, $key, 'some-value'));
        $this->assertEquals('some-value', smartGetOption(null, $key));
        $this->assertTrue(smartDeleteOption(null, $key));
        $this->assertNull(smartGetOption(null, $key));
    }

    public function testThatAllSettingsGetsDeleted()
    {
        $this->multisiteOnly();
        $this->createBlogs(2);
        $allOptionsNames = getAllOptionsNames(true);
        iterateSites(function ($site) use ($allOptionsNames) {
            foreach ($allOptionsNames as $option) {
                updateBlogOption($site->blog_id, $option, $option);
                $this->assertEquals($option, getBlogOption($site->blog_id, $option));
            }
        });
        deleteAllSettings(true, true);
        iterateSites(function ($site) use ($allOptionsNames) {
            foreach ($allOptionsNames as $option) {
                $this->assertNull(getBlogOption($site->blog_id, $option));
            }
        });
    }

    private function createBlogs(int $numberOfBlogs = 1, $blogCreationAction = null): void
    {
        $siteCount = countSites();
        for ($i = 1; $i <= $numberOfBlogs; $i++) {
            $number = $i + $siteCount;
            $blogId = $this->factory()->blog->create( [ 'domain' => 'foo-' . $number , 'path' => '/', 'title' => 'Blog ' . $number ] );
            if (is_callable($blogCreationAction)) {
                $blogCreationAction($blogId);
            }
        }
    }
}
