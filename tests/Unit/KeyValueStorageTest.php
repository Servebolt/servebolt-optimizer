<?php

namespace Unit;

use Servebolt\Optimizer\KeyValueStorage\KeyValueStorage;
use ServeboltWPUnitTestCase;

class KeyValueStorageTest extends ServeboltWPUnitTestCase
{

    private $settingsItemsExtended = [
        'some_value' => [
            'type' => 'boolean',
        ],
        'some_other_value' => [
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
        'some_value' => 'boolean',
        'some_other_value' => 'string',
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

        $this->assertNull($instance->getValue('some_value', null));
        $this->assertFalse($instance->getValue('some_value', null, false));
        $this->assertTrue($instance->setValue('some_value', true));
        $this->assertTrue($instance->getValue('some_value'));

        $this->assertFalse($instance->setValue('some_value', 'non-boolean-value'));

        $this->assertTrue($instance->setValue('some_value', false));
        $this->assertFalse($instance->getValue('some_value'));
    }

    public function testBooleanKeyValueStorageExtended(): void
    {
        $instance = KeyValueStorage::init($this->settingsItemsExtended);

        $this->assertNull($instance->getValue('some_value'));
        $this->assertFalse($instance->getValue('some_value', null, false));
        $this->assertTrue($instance->setValue('some_value', true));
        $this->assertTrue($instance->getValue('some_value'));

        $this->assertFalse($instance->setValue('some_value', 'non-boolean-value'));

        $this->assertTrue($instance->setValue('some_value', false));
        $this->assertFalse($instance->getValue('some_value'));
    }

    public function testStringKeyValueStorage(): void
    {
        $instance = KeyValueStorage::init($this->settingsItems);

        $this->assertNull($instance->getValue('some_other_value'));
        $this->assertEquals('default-value', $instance->getValue('some_other_value', null, 'default-value'));
        $this->assertTrue($instance->setValue('some_other_value', 'some-value'));
        $this->assertEquals('some-value', $instance->getValue('some_other_value'));

        $this->assertFalse($instance->setValue('some_other_value', true));

        $this->assertTrue($instance->setValue('some_other_value', 'some-other-value'));
        $this->assertEquals('some-other-value', $instance->getValue('some_other_value'));
    }

    public function testStringKeyValueStorageExtended(): void
    {
        $instance = KeyValueStorage::init($this->settingsItems);

        $this->assertNull($instance->getValue('some_other_value'));
        $this->assertEquals('default-value', $instance->getValue('some_other_value', null, 'default-value'));
        $this->assertTrue($instance->setValue('some_other_value', 'some-value'));
        $this->assertEquals('some-value', $instance->getValue('some_other_value'));

        $this->assertFalse($instance->setValue('some_other_value', true));

        $this->assertTrue($instance->setValue('some_other_value', 'some-other-value'));
        $this->assertEquals('some-other-value', $instance->getValue('some_other_value'));
    }

    public function testRadioKeyValueStorage(): void
    {
        $instance = KeyValueStorage::init($this->settingsItemsExtended);

        $this->assertTrue($instance->settingExists('radio-value'));
        $this->assertFalse($instance->settingExists('radio-value-not-existing'));

        $this->assertNull($instance->getValue('radio-value'));
        $this->assertEquals('default-value', $instance->getValue('radio-value', null, 'default-value'));

        $this->assertTrue($instance->setValue('radio-value', 'value-1'));
        $this->assertEquals('value-1', $instance->getValue('radio-value'));

        $this->assertFalse($instance->setValue('radio-value', 'invalid-value'));
        $this->assertEquals('value-1', $instance->getValue('radio-value'));

        $this->assertTrue($instance->setValue('radio-value', 'value-2'));
        $this->assertEquals('value-2', $instance->getValue('radio-value'));
    }
}
