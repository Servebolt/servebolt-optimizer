<?php

namespace Unit;

use Servebolt\Optimizer\Utils\KeyValueStorage\KeyValueStorage;
use ServeboltWPUnitTestCase;

class KeyValueStorageTest extends ServeboltWPUnitTestCase
{

    private $settingsItemsExtended = [
        'a_key' => [
            'type' => 'boolean',
        ],
        'another_key' => [
            'type' => 'string',
        ],
        'radio_value' => [
            'type' => 'radio',
            'values' => [
                'value-1',
                'value-2',
                'value-3',
            ],
        ],
    ];

    private $settingsItems = [
        'a_key' => 'boolean',
        'another_key' => 'string',
    ];

    public function testSettingsRegisterExtendedDefinition(): void
    {
        $instance = KeyValueStorage::init($this->settingsItemsExtended);
        $this->assertEquals($this->settingsItemsExtended, $instance->getSettingsItems());
        $this->assertEquals(array_keys($this->settingsItemsExtended), $instance->getSettingsItemKeys());
    }

    public function testSettingsRegister(): void
    {
        $instance = KeyValueStorage::init($this->settingsItems);
        $this->assertEquals($this->settingsItems, $instance->getSettingsItems());
        $this->assertEquals(array_keys($this->settingsItems), $instance->getSettingsItemKeys());
    }

    public function testUnregisteredSetting(): void
    {
        $instance = KeyValueStorage::init($this->settingsItems);
        $this->assertFalse($instance->setValue('some_unregistered_setting', 'some-value'));
        $this->assertNull($instance->getValue('some_unregistered_setting'));
    }

    public function testBooleanKeyValueStorage(): void
    {
        $instance = KeyValueStorage::init($this->settingsItems);

        $this->assertFalse($instance->getValue('a_key', null));
        $this->assertFalse($instance->getValue('a_key', null, false));
        $this->assertTrue($instance->setValue('a_key', true));
        $this->assertTrue($instance->getValue('a_key'));

        $this->assertFalse($instance->setValue('a_key', 'non-boolean-value'));

        $this->assertTrue($instance->setValue('a_key', false));
        $this->assertFalse($instance->getValue('a_key'));
    }

    public function testBooleanKeyValueStorageExtended(): void
    {
        $instance = KeyValueStorage::init($this->settingsItemsExtended);

        $this->assertFalse($instance->getValue('a_key'));
        $this->assertFalse($instance->getValue('a_key', null, false));
        $this->assertTrue($instance->setValue('a_key', true));
        $this->assertTrue($instance->getValue('a_key'));

        $this->assertFalse($instance->setValue('a_key', 'non-boolean-value'));

        $this->assertTrue($instance->setValue('a_key', false));
        $this->assertFalse($instance->getValue('a_key'));
    }

    public function testStringKeyValueStorage(): void
    {
        $instance = KeyValueStorage::init($this->settingsItems);

        $this->assertEquals('', $instance->getValue('another-key'));
        $this->assertEquals('default-value', $instance->getValue('another-key', null, 'default-value'));
        $this->assertTrue($instance->setValue('another-key', 'some-value'));
        $this->assertEquals('some-value', $instance->getValue('another-key'));
        $this->assertFalse($instance->setValue('another-key', true));
        $this->assertTrue($instance->setValue('another-key', 'some-other-value'));
        $this->assertEquals('some-other-value', $instance->getValue('another-key'));
    }

    public function testStringKeyValueStorageExtended(): void
    {
        $instance = KeyValueStorage::init($this->settingsItems);

        $this->assertEquals('', $instance->getValue('another-key'));
        $this->assertEquals('default-value', $instance->getValue('another-key', null, 'default-value'));
        $this->assertTrue($instance->setValue('another-key', 'some-value'));
        $this->assertEquals('some-value', $instance->getValue('another-key'));

        $this->assertFalse($instance->setValue('another-key', true));

        $this->assertTrue($instance->setValue('another-key', 'some-other-value'));
        $this->assertEquals('some-other-value', $instance->getValue('another-key'));
    }

    public function testThatUnderscoreAndHyphenWorksInItemName()
    {
        $instance = KeyValueStorage::init($this->settingsItems);
        $this->assertEquals('', $instance->getValue('another-key'));
        $this->assertEquals('', $instance->getValue('another_key'));
    }

    public function testRadioKeyValueStorage(): void
    {
        $instance = KeyValueStorage::init($this->settingsItemsExtended);

        $this->assertTrue($instance->settingExists('radio-value'));
        $this->assertFalse($instance->settingExists('radio-value-not-existing'));

        $this->assertFalse($instance->getValue('radio-value'));
        $this->assertFalse($instance->getValue('radio-value', null, 'default-value'));
        $this->assertEquals('value-1', $instance->getValue('radio-value', null, 'value-1'));

        $this->assertTrue($instance->setValue('radio-value', 'value-1'));
        $this->assertEquals('value-1', $instance->getValue('radio-value'));

        $this->assertFalse($instance->setValue('radio-value', 'invalid-value'));
        $this->assertEquals('value-1', $instance->getValue('radio-value'));

        $this->assertTrue($instance->setValue('radio-value', 'value-2'));
        $this->assertEquals('value-2', $instance->getValue('radio-value'));
    }
}
