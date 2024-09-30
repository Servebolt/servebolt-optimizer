<?php

namespace Unit;

use ServeboltWPUnitTestCase;
use Unit\Traits\MultisiteTrait;

use function Servebolt\Optimizer\Helpers\smartAddOrUpdateOption;
use function Servebolt\Optimizer\Helpers\smartUpdateOption;
use function Servebolt\Optimizer\Helpers\smartGetOption;
use function Servebolt\Optimizer\Helpers\smartDeleteOption;

use function Servebolt\Optimizer\Helpers\addOrUpdateOption;
use function Servebolt\Optimizer\Helpers\updateOption;
use function Servebolt\Optimizer\Helpers\getOption;
use function Servebolt\Optimizer\Helpers\deleteOption;

//use function Servebolt\Optimizer\Helpers\addOrUpdateBlogOption; // Already tested through function "smartAddOrUpdateOption" in multisite context
use function Servebolt\Optimizer\Helpers\updateBlogOption;
use function Servebolt\Optimizer\Helpers\getBlogOption;
use function Servebolt\Optimizer\Helpers\deleteBlogOption;

use function Servebolt\Optimizer\Helpers\updateSiteOption;
use function Servebolt\Optimizer\Helpers\getSiteOption;
use function Servebolt\Optimizer\Helpers\deleteSiteOption;

use function Servebolt\Optimizer\Helpers\clearDefaultOption;
use function Servebolt\Optimizer\Helpers\clearOptionsOverride;
use function Servebolt\Optimizer\Helpers\getOptionName;
use function Servebolt\Optimizer\Helpers\deleteAllSettings;
use function Servebolt\Optimizer\Helpers\deleteAllSiteSettings;
use function Servebolt\Optimizer\Helpers\getAllOptionsNames;
use function Servebolt\Optimizer\Helpers\getAllSiteOptionNames;
use function Servebolt\Optimizer\Helpers\iterateSites;
use function Servebolt\Optimizer\Helpers\listenForCheckboxOptionChange;
use function Servebolt\Optimizer\Helpers\listenForCheckboxOptionUpdates;
use function Servebolt\Optimizer\Helpers\listenForOptionChange;
use function Servebolt\Optimizer\Helpers\setDefaultOption;
use function Servebolt\Optimizer\Helpers\setOptionOverride;
use function Servebolt\Optimizer\Helpers\skipNextListen;

class OptionsHelpersTest extends ServeboltWPUnitTestCase
{
    use MultisiteTrait;

    public function testSmartAddOrUpdateBlogOptionsHelpers()
    {
        $this->skipWithoutMultisite();
        $this->createBlogs(3);
        iterateSites(function ($site) {
            global $wpdb;
            $optionsName = 'add-or-update';

            global $wp_version;
            $false = (version_compare($wp_version, '6.6.1', '<')) ? 'no' : 'off';
            $true = (version_compare($wp_version, '6.6.1', '<')) ? 'yes' : 'auto';

            $this->assertTrue(smartAddOrUpdateOption($site->blog_id, $optionsName, 'some-value'));
            $this->assertEquals($false, $this->getOptionsAutoloadState($wpdb->options, getOptionName($optionsName)));

            $this->assertTrue(smartAddOrUpdateOption($site->blog_id, $optionsName, 'some-other-value'));
            $this->assertEquals($false, $this->getOptionsAutoloadState($wpdb->options, getOptionName($optionsName)));

            $optionsName = 'add-or-update-2';
            $this->assertTrue(smartUpdateOption($site->blog_id, $optionsName, 'some-value'));
            $this->assertEquals($true, $this->getOptionsAutoloadState($wpdb->options, getOptionName($optionsName)));
        }, true);
    }

    public function testSmartOptionsHelpersMultisite()
    {
        $this->skipWithoutMultisite();
        $this->createBlogs(3);
        iterateSites(function ($site) {
            $key = 'some-option-for-testing';
            $this->assertNull(smartGetOption($site->blog_id, $key));
            $this->assertTrue(smartUpdateOption($site->blog_id, $key, 'some-value'));
            $this->assertEquals('some-value', smartGetOption($site->blog_id, $key));
            $this->assertTrue(smartDeleteOption($site->blog_id, $key));
            $this->assertNull(smartGetOption($site->blog_id, $key));
        }, true);
        $this->deleteBlogs();
    }

    public function testSmartOptionsHelpersSinglesite()
    {
        $key = 'some-option-for-testing-smart-option';
        $this->assertNull(smartGetOption(null, $key));
        $this->assertTrue(smartUpdateOption(null, $key, 'some-value'));
        $this->assertEquals('some-value', smartGetOption(null, $key));
        $this->assertTrue(smartDeleteOption(null, $key));
        $this->assertNull(smartGetOption(null, $key));
    }

    public function testThatOptionNameIsCorrect()
    {
        $this->assertEquals('servebolt_some_option', getOptionName('some_option'));
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
        $this->skipWithoutMultisite();
        $this->createBlogs(2);
        iterateSites(function ($site) {
            $key = 'some-option-for-testing-multisite';
            $this->assertNull(getBlogOption($site->blog_id, $key));
            $this->assertTrue(updateBlogOption($site->blog_id, $key, 'some-value'));
            $this->assertEquals('some-value', getBlogOption($site->blog_id, $key));
            $this->assertTrue(deleteBlogOption($site->blog_id, $key));
            $this->assertNull(getBlogOption($site->blog_id, $key));
        }, true);
        $this->deleteBlogs();
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
        $this->skipWithoutMultisite();
        $this->createBlogs(2);
        iterateSites(function ($site) {
            $override = function($value) {
                return 'override';
            };
            $key = 'some-overrideable-blog-options-key';
            $fullKey = 'servebolt_' . $key;
            deleteBlogOption($site->blog_id, $key);
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
        $this->deleteBlogs();
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
        $this->skipWithoutMultisite();
        $this->createBlogs(3);
        iterateSites(function ($site) {
            $key = 'default-options-value-test-key';
            deleteBlogOption($site->blog_id, $key, 'an-actual-value');
            $this->assertEquals('default-value', getBlogOption($site->blog_id, $key, 'default-value'));
            updateBlogOption($site->blog_id, $key, 'an-actual-value');
            $this->assertNotEquals('default-value', getBlogOption($site->blog_id, $key, 'default-value'));
            $this->assertEquals('an-actual-value', getBlogOption($site->blog_id, $key, 'default-value'));
        }, true);
        $this->deleteBlogs();
    }

    public function testDefaultValuesForSmartOptionsHelpers()
    {
        $key = 'default-options-value-test-key';
        smartDeleteOption(null, $key);
        $this->assertEquals('default-value', smartGetOption(null, $key, 'default-value'));
        smartUpdateOption(null, $key, 'an-actual-value');
        $this->assertNotEquals('default-value', smartGetOption(null, $key, 'default-value'));
        $this->assertEquals('an-actual-value', smartGetOption(null, $key, 'default-value'));
    }

    private function getOptionsAutoloadState($tableName, $optionsName)
    {
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$tableName} WHERE option_name = %s", $optionsName));
        return $row->autoload;
    }

    public function testAddOrUpdateOptionsHelpers()
    {
        global $wpdb;
        $optionsName = 'add-or-update';

        global $wp_version;
        $false = (version_compare($wp_version, '6.6.1', '<')) ? 'no' : 'off';
        $true = (version_compare($wp_version, '6.6.1', '<')) ? 'yes' : 'auto';
        $this->assertTrue(addOrUpdateOption($optionsName, 'some-value'));

        $this->assertEquals($false, $this->getOptionsAutoloadState($wpdb->options, getOptionName($optionsName)));

        $this->assertTrue(addOrUpdateOption($optionsName, 'some-other-value'));
        $this->assertEquals($false, $this->getOptionsAutoloadState($wpdb->options, getOptionName($optionsName)));

        $optionsName = 'add-or-update-2';
        $this->assertTrue(updateOption($optionsName, 'some-value'));
        $this->assertEquals($true, $this->getOptionsAutoloadState($wpdb->options, getOptionName($optionsName)));
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

    public function testThatWeCanSkipOptionChangeOrUpdateEvent()
    {
        $key = 'some-checkbox-value';
        $callCount = 0;
        listenForCheckboxOptionUpdates($key, function($wasActive, $isActive, $didChange, $optionName) use (&$callCount) {
            $callCount++;
        });
        updateOption($key, 1);
        updateOption($key, 1);
        skipNextListen($key);
        updateOption($key, 1);
        updateOption($key, 0);
        updateOption($key, 0);
        updateOption($key, 1);
        $this->assertEquals(5, $callCount);
    }

    public function testThatWeCanDetectCheckboxOptionUpdateUsingFunctionClosure()
    {
        $key = 'some-checkbox-value';
        $callCount = 0;
        listenForCheckboxOptionUpdates($key, function($wasActive, $isActive, $didChange, $optionName) use (&$callCount) {
            $callCount++;
        });
        updateOption($key, 1);
        updateOption($key, 1);
        updateOption($key, 1);
        updateOption($key, 0);
        updateOption($key, 0);
        updateOption($key, 1);
        $this->assertEquals(6, $callCount);
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

    public function testThatWeCanDetectOptionValueChangeUsingFunctionClosureAndNonServeboltOptions()
    {
        $key = 'some-string-value';
        $callCount = 0;
        listenForOptionChange($key, function($newValue, $oldValue, $optionName) use (&$callCount) {
            $callCount++;
        }, false);
        update_option($key, 12);
        $this->assertEquals(1, $callCount);
    }

    public function testThatWeCanDetectOptionValueChangeUsingFunctionClosureAndNonStrictComparison()
    {
        $key = 'some-string-value';
        $callCount = 0;
        listenForOptionChange($key, function($newValue, $oldValue, $optionName) use (&$callCount) {
            $callCount++;
        }, true, false);
        updateOption($key, 12);
        updateOption($key, '12');
        updateOption($key, '12');
        updateOption($key, 12);
        $this->assertEquals(1, $callCount);
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

    public function testThatAllSiteSettingsGetsDeleted()
    {
        $this->skipWithoutMultisite();
        $allSiteOptionsNames = getAllSiteOptionNames(true);
        foreach ($allSiteOptionsNames as $option) {
            updateSiteOption($option, $option);
            $this->assertEquals($option, getSiteOption($option));
        }
        deleteAllSiteSettings(true);
        foreach ($allSiteOptionsNames as $option) {
            $value = getSiteOption($option);
            $this->assertNull($value);
        }
    }

    public function testThatAllSettingsGetsDeleted()
    {
        $this->skipWithoutMultisite();
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
                    case 'prefetch_file_style_switch':
                    case 'prefetch_file_script_switch':
                        //case 'prefetch_file_menu_switch':
                    case 'custom_cache_ttl_switch':
                    case 'cache_purge_auto':
                    case 'cache_purge_auto_on_slug_change':
                    case 'cache_purge_auto_on_deletion':
                    case 'cache_purge_auto_on_attachment_update':
                    case 'menu_cache_auto_cache_purge_on_menu_update':
                    case 'menu_cache_auto_cache_purge_on_front_page_settings_update':
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
                        /*
                        if (!is_null($value)) {
                            die($option);
                        }
                        */
                        $this->assertNull($value);
                        break;
                }
            }
        });
        $this->deleteBlogs();
    }
}
