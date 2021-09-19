<?php

namespace Unit;

use Unit\Traits\AttachmentTrait;
use Unit\Traits\MultisiteTrait;
use Servebolt\Optimizer\Admin\CloudflareImageResize\CloudflareImageResize;
use Servebolt\Optimizer\Utils\EnvFile\Reader as EnvFileReader;
use Servebolt\Optimizer\Utils\Queue\QueueItem;
use ServeboltWPUnitTestCase;
use function Servebolt\Optimizer\Helpers\arrayGet;
use function Servebolt\Optimizer\Helpers\booleanToStateString;
use function Servebolt\Optimizer\Helpers\booleanToString;
use function Servebolt\Optimizer\Helpers\camelCaseToSnakeCase;
use function Servebolt\Optimizer\Helpers\clearDefaultOption;
use function Servebolt\Optimizer\Helpers\clearOptionsOverride;
use function Servebolt\Optimizer\Helpers\deleteAllSettings;
use function Servebolt\Optimizer\Helpers\deleteBlogOption;
use function Servebolt\Optimizer\Helpers\deleteOption;
use function Servebolt\Optimizer\Helpers\deleteSiteOption;
use function Servebolt\Optimizer\Helpers\getAllImageSizesByImage;
use function Servebolt\Optimizer\Helpers\getAllOptionsNames;
use function Servebolt\Optimizer\Helpers\getBlogOption;
use function Servebolt\Optimizer\Helpers\getCurrentPluginVersion;
use function Servebolt\Optimizer\Helpers\getFiltersForHook;
use function Servebolt\Optimizer\Helpers\getOption;
use function Servebolt\Optimizer\Helpers\getSiteOption;
use function Servebolt\Optimizer\Helpers\getTaxonomyFromTermId;
use function Servebolt\Optimizer\Helpers\getTaxonomySingularName;
use function Servebolt\Optimizer\Helpers\iterateSites;
use function Servebolt\Optimizer\Helpers\javascriptRedirect;
use function Servebolt\Optimizer\Helpers\listenForCheckboxOptionChange;
use function Servebolt\Optimizer\Helpers\listenForOptionChange;
use function Servebolt\Optimizer\Helpers\pickupValueFromFilter;
use function Servebolt\Optimizer\Helpers\setDefaultOption;
use function Servebolt\Optimizer\Helpers\setOptionOverride;
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
use function Servebolt\Optimizer\Helpers\getWebrootPath;
use function Servebolt\Optimizer\Helpers\strEndsWith;
use function Servebolt\Optimizer\Helpers\updateBlogOption;
use function Servebolt\Optimizer\Helpers\updateOption;
use function Servebolt\Optimizer\Helpers\updateSiteOption;
use function Servebolt\Optimizer\Helpers\view;
use function Servebolt\Optimizer\Helpers\writeLog;

class HelpersTest extends ServeboltWPUnitTestCase
{
    use MultisiteTrait, AttachmentTrait;

    private function activateSbDebug(): void
    {
        if (!defined('SB_DEBUG')) {
            define('SB_DEBUG', true);
        }
    }

    public function testWriteToLog()
    {
        $errorMessage = 'error-message-' . uniqid();
        $errorFilePath = ini_get('error_log');
        $this->assertNotContains($errorMessage, exec('tail -n 1 ' . $errorFilePath));
        writeLog($errorMessage);
        $this->assertContains($errorMessage, exec('tail -n 1 ' . $errorFilePath));
    }

    public function testThatViewIsIncludedAndThatArgumentsAreAvailable()
    {
        add_filter('sb_optimizer_view_folder_path', function() {
            return __DIR__ . '/ViewsForTest/';
        });
        $arguments = [
            'lorem' => true,
            'ipsum' => false,
        ];
        $output = view('test', $arguments, false);
        $this->assertEquals(json_encode($arguments), $output);
    }

    public function testThatWeCanGetTheWebrootFolderPath(): void
    {
        $this->assertContains('tests/bin/tmp/wordpress/', getWebrootPath());
        $this->activateSbDebug();
        $this->assertEquals('/kunder/serveb_1234/custom_4321/public', getWebrootPath());
        add_filter('sb_optimizer_wp_webroot_path', function() {
            return '/some/path/to/somewhere/';
        });
        $this->assertEquals('/some/path/to/somewhere/', getWebrootPath());
    }

    public function testThatWeCanGetAdminUrlFromHomePath(): void
    {
        $this->activateSbDebug();

        // Test with site ID extracted from webroot path
        $this->assertEquals('https://admin.servebolt.com/siteredirect/?site=4321', getServeboltAdminUrl());
        $this->assertEquals('https://admin.servebolt.com/siteredirect/?page=accelerated-domains&site=4321', getServeboltAdminUrl(['page' => 'accelerated-domains']));

        // Create custom env file reader instance
        EnvFileReader::destroyInstance();
        EnvFileReader::getInstance(__DIR__ . '/EnvFile/');

        // Test with site ID extracted from environment file
        $this->assertEquals('https://admin.servebolt.com/siteredirect/?site=56789', getServeboltAdminUrl([]));
        $this->assertEquals('https://admin.servebolt.com/siteredirect/?page=accelerated-domains&site=56789', getServeboltAdminUrl(['page' => 'accelerated-domains']));
        $this->assertEquals('https://admin.servebolt.com/siteredirect/?page=accelerated-domains&some=parameter&another=one&site=56789', getServeboltAdminUrl(['page' => 'accelerated-domains', 'some' => 'parameter', 'another' => 'one']));
        $this->assertEquals('https://admin.servebolt.com/siteredirect/?page=accelerated-domains&webhost_id=69&some=parameter&another=one&site=56789', getServeboltAdminUrl(['page' => 'accelerated-domains', 'webhost_id' => '69', 'some' => 'parameter', 'another' => 'one']));
        $this->assertEquals('https://admin.servebolt.com/siteredirect/?page=accelerated-domains&site=56789', getServeboltAdminUrl('accelerated-domains'));

        // Test with site ID extracted from webroot path
        EnvFileReader::disable();
        $this->assertEquals('https://admin.servebolt.com/siteredirect/?site=4321', getServeboltAdminUrl());

        // Test with site ID extracted from environment file
        EnvFileReader::enable();
        $this->assertEquals('https://admin.servebolt.com/siteredirect/?site=56789', getServeboltAdminUrl());

        // Revert env file reader to default state
        EnvFileReader::destroyInstance();
        EnvFileReader::getInstance();
    }

    public function testThatTestConstantGetsSet(): void
    {
        $this->assertTrue(defined('WP_TESTS_ARE_RUNNING'));
        $this->assertTrue(WP_TESTS_ARE_RUNNING);
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
        $this->assertRegExp('/^(\d\.){1,2}(\d)$/', $versionNumber);
        //$this->assertRegExp('/^([0-9]+)\.([0-9]+)\.([0-9]+)(?:-([0-9A-Za-z-]+(?:\.[0-9A-Za-z-]+)*))?(?:\+[0-9A-Za-z-]+)?$/', $versionNumber);
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
            $override = function($value) {
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
                $value = getBlogOption($site->blog_id, $option);
                switch ($option) {
                    // Default options
                    //case 'prefetch_file_style_switch':
                    //case 'prefetch_file_script_switch':
                    //case 'prefetch_file_menu_switch':
                    case 'cache_purge_auto':
                    case 'cache_purge_auto_on_slug_change':
                    case 'cache_purge_auto_on_deletion':
                    case 'cache_purge_auto_on_attachment_update':
                    case 'custom_cache_ttl_switch':
                        $this->assertTrue($value);
                        break;
                    case 'fpc_settings':
                        $this->assertIsArray($value);
                        $this->assertEquals(['all' => 1], $value);
                        break;
                    case 'cache_ttl_by_post_type':
                        $this->assertIsArray($value);
                        $this->assertArrayHasKey('post', $value);
                        $this->assertArrayHasKey('page', $value);
                        $this->assertEquals('default', $value['post']);
                        $this->assertEquals('default', $value['page']);
                        break;
                    case 'cache_ttl_by_taxonomy':
                        $this->assertIsArray($value);
                        $this->assertArrayHasKey('category', $value);
                        $this->assertArrayHasKey('post_tag', $value);
                        $this->assertArrayHasKey('post_format', $value);
                        $this->assertEquals('default', $value['category']);
                        $this->assertEquals('default', $value['post_tag']);
                        $this->assertEquals('default', $value['post_format']);
                        break;
                    default:
                        $this->assertNull($value);
                        break;
                }
            }
        });
    }

    public function testThatWeCanOverrideOptions(): void
    {
        $optionsKey = 'override-test';
        $value = 'some-value';
        $overrideValue = 'override-value';
        $this->assertNull(getOption($optionsKey));
        updateOption($optionsKey, $value);
        $this->assertEquals($value, getOption($optionsKey));
        setOptionOverride($optionsKey, $overrideValue);
        $this->assertEquals($overrideValue, getOption($optionsKey));
        clearOptionsOverride($optionsKey);
        $this->assertEquals($value, getOption($optionsKey));
    }

    public function testThatWeCanOverrideOptionsWithWpFunctionClosure(): void
    {
        $optionsKey = 'wp-override-test';
        $this->assertNull(getOption($optionsKey));
        $value = 'some-value';
        updateOption($optionsKey, $value);
        $this->assertEquals($value, getOption($optionsKey));
        setOptionOverride($optionsKey, '__return_true');
        $this->assertEquals(true, getOption($optionsKey));
        clearOptionsOverride($optionsKey, '__return_true');
        $this->assertEquals($value, getOption($optionsKey));
    }

    public function testThatWeCanOverrideOptionsWithFunctionClosure(): void
    {
        $optionsKey = 'override-test';
        $value = 'some-value';
        $overrideValue = 'override-value';
        $overrideValueClosure = function() use ($overrideValue) {
            return $overrideValue;
        };
        $this->assertNull(getOption($optionsKey));
        updateOption($optionsKey, $value);
        $this->assertEquals($value, getOption($optionsKey));
        setOptionOverride($optionsKey, $overrideValueClosure);
        $this->assertEquals($overrideValue, getOption($optionsKey));
        clearOptionsOverride($optionsKey, $overrideValueClosure);
        $this->assertEquals($value, getOption($optionsKey));
    }

    public function testThatWeCanSetADefaultOptionsValueWithFunctionClosure(): void
    {
        $optionsKey = 'default-options-test';
        $value = 'some-value';
        $defaultValue = 'default-value';
        $defaultValueClosure = function() use ($defaultValue) {
            return $defaultValue;
        };
        $this->assertNull(getOption($optionsKey));
        setDefaultOption($optionsKey, $defaultValueClosure);
        $this->assertEquals($defaultValue, getOption($optionsKey));
        updateOption($optionsKey, $value);
        $this->assertEquals($value, getOption($optionsKey));
        deleteOption($optionsKey);
        clearDefaultOption($optionsKey, $defaultValueClosure);
        $this->assertNull(getOption($optionsKey));
    }

    public function testThatWeCanSetADefaultOptionsValue(): void
    {
        $optionsKey = 'default-options-test';
        $value = 'some-value';
        $defaultValue = 'default-value';
        $this->assertNull(getOption($optionsKey));
        setDefaultOption($optionsKey, $defaultValue);
        $this->assertEquals($defaultValue, getOption($optionsKey));
        updateOption($optionsKey, $value);
        $this->assertEquals($value, getOption($optionsKey));
        deleteOption($optionsKey);
        clearDefaultOption($optionsKey);
        $this->assertNull(getOption($optionsKey));
    }

    public function testThatWeCanDetectCheckboxOptionChangeUsingFunctionClosure()
    {
        $key = 'some-checkbox-value';
        $callCount = 0;
        listenForCheckboxOptionChange($key, function($wasActive, $isActive, $optionName) use (&$callCount) {
            $callCount++;
        });
        updateOption($key, 1);
        updateOption($key, 1);
        updateOption($key, 1);
        updateOption($key, 0);
        updateOption($key, 0);
        updateOption($key, 1);
        $this->assertEquals(3, $callCount);
    }

    public function testThatWeCanDetectCheckboxOptionChangeUsingActions()
    {
        $key = 'some-checkbox-value';
        $action = 'some_action';
        $this->assertEquals(0, did_action('servebolt_' . $action));
        listenForCheckboxOptionChange($key, $action);
        updateOption($key, 1);
        updateOption($key, 1);
        $this->assertEquals(1, did_action('servebolt_' . $action));
        updateOption($key, 0);
        updateOption($key, 1);
        $this->assertEquals(3, did_action('servebolt_' . $action));
    }

    public function testThatWeCanDetectMultipleCheckboxOptionChangeUsingActions()
    {
        $keys = [
            'some-checkbox-value-1',
            'some-checkbox-value-2',
            'some-checkbox-value-3',
        ];
        $action = 'some_random_action';
        $this->assertEquals(0, did_action('servebolt_' . $action));
        listenForCheckboxOptionChange($keys, $action);
        updateOption($keys[0], 1);
        updateOption($keys[0], 1);
        $this->assertEquals(1, did_action('servebolt_' . $action));
        updateOption($keys[0], 0);
        updateOption($keys[0], 1);
        $this->assertEquals(3, did_action('servebolt_' . $action));

        updateOption($keys[2], 1);
        updateOption($keys[2], 1);
        updateOption($keys[1], 1);
        updateOption($keys[1], 1);
        $this->assertEquals(5, did_action('servebolt_' . $action));
    }

    public function testThatWeCanDetectOptionValueChangeUsingFunctionClosure()
    {
        $key = 'some-string-value';
        $callCount = 0;
        listenForOptionChange($key, function($newValue, $oldValue, $optionName) use (&$callCount) {
            $callCount++;
        });
        updateOption($key, 'value');
        updateOption($key, 'value');
        updateOption($key, 'value');
        updateOption($key, 'another-value');
        updateOption($key, 'another-value');
        updateOption($key, 'a-third-value');
        $this->assertEquals(3, $callCount);
    }

    public function testThatWeCanDetectOptionValueChangeUsingActions()
    {
        $key = 'some-string-value-2';
        $action = 'some_action_for_testing';
        $this->assertEquals(0, did_action('servebolt_' . $action));
        listenForOptionChange($key, $action);
        updateOption($key, 'lorem-ipsum');
        updateOption($key, 'lorem-ipsum');
        $this->assertEquals(1, did_action('servebolt_' . $action));
        updateOption($key, 'lipsum');
        updateOption($key, 'lorem-ipsum');
        $this->assertEquals(3, did_action('servebolt_' . $action));
    }

    public function testThatWeCanDetectMultipleOptionValuesChangeUsingActions()
    {
        $keys = [
            'some-string-value-1',
            'some-string-value-2',
            'some-string-value-3',
        ];
        $action = 'some_action_for_testing_2';
        $this->assertEquals(0, did_action('servebolt_' . $action));
        listenForOptionChange($keys, $action);
        updateOption($keys[0], 'lorem-ipsum');
        updateOption($keys[0], 'lorem-ipsum');
        $this->assertEquals(1, did_action('servebolt_' . $action));
        updateOption($keys[0], 'lorem-ipsum-2');
        updateOption($keys[0], 'lorem-ipsum');
        $this->assertEquals(3, did_action('servebolt_' . $action));

        updateOption($keys[2], 'lorem-ipsum-3');
        updateOption($keys[2], 'lorem-ipsum-3');
        updateOption($keys[1], 'lorem-ipsum-3');
        updateOption($keys[1], 'lorem-ipsum-3');
        $this->assertEquals(5, did_action('servebolt_' . $action));
    }

    public function testJavascriptRedirect()
    {
        ob_start();
        $url = 'https://example.org/';
        javascriptRedirect($url);
        $output = ob_get_contents();
        ob_end_clean();
        $expected = '<script> window.location = "' . $url . '"; </script>';
        $this->assertEquals($expected, trim($output));
    }

    public function testThatWeCanGetTaxonomySlug()
    {
        $taxonomyObject = getTaxonomyFromTermId(1);
        $this->assertEquals('category', $taxonomyObject->name);
    }

    public function testThatWeCanGetTaxonomySingularName()
    {
        $this->assertEquals('category', getTaxonomySingularName(1));
    }

    public function testThatWeCanGetFiltersByHook()
    {
        $hookName = 'some-custom-hook';
        $this->assertNull(getFiltersForHook($hookName));
        add_filter($hookName, '__return_true');
        $this->assertIsObject(getFiltersForHook($hookName));
        remove_filter($hookName, '__return_true');
        $this->assertNull(getFiltersForHook($hookName));
    }

    public function testThatWeCanGetAllImageUrls()
    {
        add_image_size('69x69', 69, 69);
        if ($attachmentId = $this->createAttachment('woocommerce-placeholder.png')) {
            $filename = basename(get_attached_file($attachmentId));
            if (!preg_match('/^(.+)-([0-9]+)\.png$/', $filename, $matches)) {
                $this->deleteAttachment($attachmentId);
                $this->fail('Could not test image size URLs');
                return;
            }
            $baseUrl = get_site_url() . '/wp-content/uploads/2021/07/woocommerce-placeholder-' . $matches[2];
            $expectedArray = [
                $baseUrl . '-150x150.png',
                $baseUrl . '-300x300.png',
                $baseUrl . '-768x768.png',
                $baseUrl . '-1024x1024.png',
                $baseUrl . '.png',
                $baseUrl . '-69x69.png',
            ];
            $this->assertEquals($expectedArray, getAllImageSizesByImage($attachmentId));
            $this->deleteAttachment($attachmentId);
        }
    }

    public function testThatWeCanPickUpValueFromFilter()
    {
        $key = 'some_filter_to_test';
        $this->assertNull(pickupValueFromFilter($key));
        add_filter('some_filter_to_test', function() {
            return 'value';
        });
        $this->assertEquals('value', pickupValueFromFilter($key));
        $this->assertNull(pickupValueFromFilter($key));

        add_filter('some_filter_to_test', function() {
            return 'value';
        });
        $this->assertEquals('value', pickupValueFromFilter($key, false));
        $this->assertEquals('value', pickupValueFromFilter($key));
        $this->assertNull(pickupValueFromFilter($key));
    }
}
