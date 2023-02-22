<?php

namespace Unit;

use Unit\Traits\AttachmentTrait;
use Unit\Traits\MultisiteTrait;
use Unit\Traits\EnvFile\EnvFileReaderTrait;
use Servebolt\Optimizer\Admin\CloudflareImageResize\CloudflareImageResize;
use Servebolt\Optimizer\Utils\EnvFile\Reader as EnvFileReader;
use Servebolt\Optimizer\Utils\Queue\QueueItem;
use ServeboltWPUnitTestCase;
use function Servebolt\Optimizer\Helpers\arrayGet;
use function Servebolt\Optimizer\Helpers\booleanToStateString;
use function Servebolt\Optimizer\Helpers\booleanToString;
use function Servebolt\Optimizer\Helpers\camelCaseToSnakeCase;
use function Servebolt\Optimizer\Helpers\convertObjectToArray;
use function Servebolt\Optimizer\Helpers\generateRandomInteger;
use function Servebolt\Optimizer\Helpers\getAllImageSizesByImage;
use function Servebolt\Optimizer\Helpers\getCurrentPluginVersion;
use function Servebolt\Optimizer\Helpers\getFiltersForHook;
use function Servebolt\Optimizer\Helpers\getMainSiteBlogId;
use function Servebolt\Optimizer\Helpers\getSiteId;
use function Servebolt\Optimizer\Helpers\getSiteIdFromEnvFile;
use function Servebolt\Optimizer\Helpers\getSiteIdFromWebrootPath;
use function Servebolt\Optimizer\Helpers\getTaxonomyFromTermId;
use function Servebolt\Optimizer\Helpers\getTaxonomySingularName;
use function Servebolt\Optimizer\Helpers\getWebrootPathFromEnvFile;
use function Servebolt\Optimizer\Helpers\getWebrootPathFromWordPress;
use function Servebolt\Optimizer\Helpers\isLogin;
use function Servebolt\Optimizer\Helpers\isValidJson;
use function Servebolt\Optimizer\Helpers\iterateSites;
use function Servebolt\Optimizer\Helpers\javascriptRedirect;
use function Servebolt\Optimizer\Helpers\pickupValueFromFilter;
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
use function Servebolt\Optimizer\Helpers\getWebrootPath;
use function Servebolt\Optimizer\Helpers\strContains;
use function Servebolt\Optimizer\Helpers\strEndsWith;
use function Servebolt\Optimizer\Helpers\view;
use function Servebolt\Optimizer\Helpers\wpCronDisabled;
use function Servebolt\Optimizer\Helpers\writeLog;

class HelpersTest extends ServeboltWPUnitTestCase
{
    use MultisiteTrait, AttachmentTrait, EnvFileReaderTrait;

    private function activateSbDebug(): void
    {
        if (!defined('SB_DEBUG')) {
            define('SB_DEBUG', true);
        }
    }

    /**
     * This test makes sure that the php error log can be written to
     * and then read. 
     * 
     * *important* this test will fail without the error_log directive 
     * being set in the php.ini of the current cli version of php
     */
    public function testWriteToLog()
    {
        $errorMessage = 'error-message-' . uniqid();
        $errorFilePath = ini_get('error_log');
        
        $fileOutputAsArrayFail[] = exec('tail -n 1 ' . $errorFilePath);
        $this->assertNotContains($errorMessage, $fileOutputAsArrayFail);
        
        writeLog($errorMessage);
        // Get last row from error file
        // remove spaces, and everything between square brackets (timestamp)
        // add to an array so that it exactly matches the input in the correct format 
        $fileOutputAsArray[] = 
                    str_replace(" ", '', 
                        preg_replace('/\[.*\]/', '', 
                            exec('tail -n 1 ' . $errorFilePath)
                        )
                    ); 
        
        $this->assertContains($errorMessage, $fileOutputAsArray);
    }

    public function testThatViewIsIncludedAndThatArgumentsAreAvailable()
    {
        add_filter('sb_optimizer_view_folder_path', function () {
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
        self::getEnvFileReader();
        add_filter('sb_optimizer_is_hosted_at_servebolt', '__return_true');
        $this->assertEquals('/kunder/serveb_123456/optimi_56789/public', getWebrootPath());
        remove_filter('sb_optimizer_is_hosted_at_servebolt', '__return_true');
        add_filter('sb_optimizer_wp_webroot_path', function () {
            return '/some/path/to/somewhere/';
        });
        $this->assertEquals('/some/path/to/somewhere/', getWebrootPath());
    }

    public function testThatWeCanGetSiteId(): void
    {
        self::getEnvFileReader();
        $this->assertNull(getSiteId()); // Null since isHostedAtServebolt is false therefore we're not even looking at the env-file
        add_filter('sb_optimizer_wp_webroot_path_from_wp', function () {
            return '/kunder/serveb_123456/optimi_567899';
        });
        $this->assertEquals('567899', getSiteId());
        add_filter('sb_optimizer_is_hosted_at_servebolt', '__return_true');
        $this->assertEquals('56789', getSiteId());
        remove_filter('sb_optimizer_is_hosted_at_servebolt', '__return_true');
        $this->assertEquals('567899', getSiteId());
        remove_all_filters('sb_optimizer_wp_webroot_path_from_wp');
        $this->assertNull(getSiteId());
    }

    public function testThatWeCanGetSiteIdFromEnvFile(): void
    {
        self::getEnvFileReader();
        $this->assertEquals('56789', getSiteIdFromEnvFile());
        add_filter('sb_optimizer_env_file_reader_get_id', function () {
            return '123';
        });
        $this->assertEquals('123', getSiteIdFromEnvFile());
        remove_all_filters('sb_optimizer_env_file_reader_get_id');
        $this->assertEquals('56789', getSiteIdFromEnvFile());
    }

    public function testThatWeCanGetSiteIdFromWebroot(): void
    {
        self::getEnvFileReader();
        add_filter('sb_optimizer_wp_webroot_path_from_wp', function () {
            return '/kunder/serveb_123456/optimi_567899';
        });
        $this->assertEquals('567899', getSiteIdFromWebrootPath());
        add_filter('sb_optimizer_is_hosted_at_servebolt', '__return_true');
        $this->assertEquals('56789', getSiteIdFromWebrootPath());
        $this->assertEquals('567899', getSiteIdFromWebrootPath(false));
        remove_all_filters('sb_optimizer_wp_webroot_path_from_wp');
        remove_filter('sb_optimizer_is_hosted_at_servebolt', '__return_true');
    }

    public function testThatWeCanGetWebroot(): void
    {
        $this->assertContains('bin/tmp/wordpress', getWebrootPath());
        self::getEnvFileReader();
        $this->assertContains('bin/tmp/wordpress', getWebrootPath()); // Still unchanged since isHostedAtServebolt is false
        add_filter('sb_optimizer_is_hosted_at_servebolt', '__return_true');
        $this->assertEquals('/kunder/serveb_123456/optimi_56789/public', getWebrootPath()); // Resolved from env-file
        add_filter('sb_optimizer_wp_webroot_path_from_env', '__return_false'); // Prevent resolving from env-file
        add_filter('sb_optimizer_wp_webroot_path_from_wp', function () {
            return '/some/other/path/';
        });
        $this->assertEquals('/some/other/path/', getWebrootPath()); // Resolved from WP
        remove_all_filters('sb_optimizer_wp_webroot_path_from_env');
        remove_all_filters('sb_optimizer_wp_webroot_path_from_wp');
        $this->assertEquals('/kunder/serveb_123456/optimi_56789/public', getWebrootPath()); // Resolved from env-file
        remove_filter('sb_optimizer_is_hosted_at_servebolt', '__return_true');
        $this->assertContains('bin/tmp/wordpress', getWebrootPath());
    }

    public function testThatWeCanGetWebrootFromEnvFile(): void
    {
        self::getEnvFileReader();
        $this->assertEquals('/kunder/serveb_123456/optimi_56789/public', getWebrootPathFromEnvFile());
    }

    public function testThatWeCanGetWebrootFromWordPress(): void
    {
        $this->assertContains('bin/tmp/wordpress', getWebrootPathFromWordPress());
    }

    public function testThatWeCanGetAdminUrlFromHomePath(): void
    {
        $this->activateSbDebug();

        // Test with site ID extracted from webroot path
        self::getEnvFileReader();

        $this->assertNull(getServeboltAdminUrl()); // False since we're not currently hosted at Servebolt

        // Add manual path which we will extract the ID from
        add_filter('sb_optimizer_wp_webroot_path_from_wp', function () {
            return '/kunder/serveb_123456/optimi_5678999';
        });
        $this->assertEquals('https://admin.servebolt.com/siteredirect/?site=5678999', getServeboltAdminUrl());
        remove_all_filters('sb_optimizer_wp_webroot_path_from_wp');

        // Test with site ID extracted from environment file
        add_filter('sb_optimizer_is_hosted_at_servebolt', '__return_true');
        $this->assertEquals('https://admin.servebolt.com/siteredirect/?site=56789', getServeboltAdminUrl());
        $this->assertEquals('https://admin.servebolt.com/siteredirect/?page=accelerated-domains&site=56789', getServeboltAdminUrl(['page' => 'accelerated-domains']));
        $this->assertEquals('https://admin.servebolt.com/siteredirect/?page=accelerated-domains&some=parameter&another=one&site=56789', getServeboltAdminUrl(['page' => 'accelerated-domains', 'some' => 'parameter', 'another' => 'one']));
        $this->assertEquals('https://admin.servebolt.com/siteredirect/?page=accelerated-domains&webhost_id=69&some=parameter&another=one&site=56789', getServeboltAdminUrl(['page' => 'accelerated-domains', 'webhost_id' => '69', 'some' => 'parameter', 'another' => 'one']));
        $this->assertEquals('https://admin.servebolt.com/siteredirect/?page=accelerated-domains&site=56789', getServeboltAdminUrl('accelerated-domains'));

        // Test with site ID extracted from webroot path
        add_filter('sb_optimizer_env_file_reader_get_id', '__return_null');
        add_filter('sb_optimizer_wp_webroot_path_from_wp', function () {
            return '/kunder/serveb_123456/optimi_56780';
        });

        $this->assertEquals('https://admin.servebolt.com/siteredirect/?site=56780', getServeboltAdminUrl());
        remove_filter('sb_optimizer_env_file_reader_get_id', '__return_null');

        // Test with site ID extracted from environment file
        $this->assertEquals('https://admin.servebolt.com/siteredirect/?site=56789', getServeboltAdminUrl());
        remove_all_filters('sb_optimizer_wp_webroot_path_from_wp');
        remove_filter('sb_optimizer_is_hosted_at_servebolt', '__return_true');

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

    public function testRandomStringGenerator()
    {
        $this->assertEquals(20, mb_strlen(generateRandomString(20)));
    }

    public function testDisplayValueHelper()
    {
        $this->assertEquals('true', displayValue(true));
        $this->assertEquals('false', displayValue(false));
        $this->assertEquals('string', displayValue('string'));

        $array = ['test', 'test2'];
        ob_start();
        var_dump($array);
        $output = ob_get_contents();
        ob_end_clean();
        $this->assertEquals($output, displayValue($array));
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
        $array = ['array', 'with', 'some', 'values'];
        $this->assertEquals('array - with - some - values', formatArrayToCsv($array, ' - '));
    }

    public function testNaturalLanguageJoinHelper()
    {
        $this->assertEquals('"Something" and "Something"', naturalLanguageJoin(['Something', 'Something']));
        $this->assertEquals('Something and Something', naturalLanguageJoin(['Something', 'Something'], null, ''));
        $this->assertEquals('Something or Something', naturalLanguageJoin(['Something', 'Something'], 'or', ''));
        $this->assertEquals('Something, Something or Another thing', naturalLanguageJoin(['Something', 'Something', 'Another thing'], 'or', ''));
        $this->assertEquals("'Something', 'something' and 'another thing'", naturalLanguageJoin(['Something', 'something', 'another thing'], null, "'"));
    }

    public function testThatStringContains()
    {
        $this->assertTrue(strContains('some-long-string', 'long', false));
        $this->assertFalse(strContains('some-long-string', 'long2', false));
        $this->assertTrue(strContains('some-long-string', 'long', true));
        $this->assertFalse(strContains('some-long-string', 'long2', true));
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
        $versionNumber = getCurrentPluginVersion(true);
        $this->assertRegExp('/^(\d\.){1,2}(\d+)$/', $versionNumber);

        $versionNumber = getCurrentPluginVersion(false);
        $this->assertIsString($versionNumber);
        $pattern = '-(alpha|beta|rc)(|\.([0-9]{1,2}))';
        if (preg_match('/' . $pattern . '$/', $versionNumber)) {
            $this->assertRegExp('/^(\d\.){1,2}(\d+)' . $pattern . '$/', $versionNumber);
        } else {
            $this->assertRegExp('/^(\d\.){1,2}(\d+)$/', $versionNumber);
        }
    }

    public function testCountSitesHelper()
    {
        $this->skipWithoutMultisite();
        $this->assertEquals(1, countSites());
        $this->createBlogs(3);
        $this->assertEquals(4, countSites());
        $this->deleteBlogs();
        $this->assertEquals(1, countSites());
    }

    public function testGetMainSiteBlogId()
    {
        $this->skipWithoutMultisite();
        $this->assertEquals(1, getMainSiteBlogId());
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
            $filePath = get_attached_file($attachmentId);

            $filenameParts = pathinfo($filePath);
            $filenameWithoutExtension = $filenameParts['filename'];

            $baseUrl = get_site_url() . '/wp-content/uploads/' . date('Y') . '/' . date('m') . '/' . $filenameWithoutExtension;

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

    public function testThatWeCanDetermineWpCronDisabling()
    {
        add_filter('sb_optimizer_wp_cron_disabled', '__return_true');
        $this->assertTrue(wpCronDisabled());
        remove_all_filters('sb_optimizer_wp_cron_disabled');
        add_filter('sb_optimizer_wp_cron_disabled', '__return_false');
        $this->assertFalse(wpCronDisabled());
        remove_all_filters('sb_optimizer_wp_cron_disabled');
    }

    public function testRandomIntegerGenerator()
    {
        $this->assertIsInt(generateRandomInteger(0, 12));
    }

    public function testThatWeCanValidateJson()
    {
        $data = ['foo' => 'bar', 'some' => 'thing'];
        $validJson = json_encode($data);
        $this->assertTrue(isValidJson($validJson));
        $invalidJson = mb_substr($validJson, 2);
        $this->assertFalse(isValidJson($invalidJson));
    }

    public function testThatWeCanConvertObjectToArray()
    {
        $object = (object) ['some' => (object)['array', 'and', 'something', 'more']];
        $this->assertEquals(['some' => ['array', 'and', 'something', 'more']], convertObjectToArray($object));
    }

    public function testThatLoginContextIsFalse()
    {
        $this->assertFalse(isLogin());
    }
}
