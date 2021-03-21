<?php

namespace Unit;

use WP_UnitTestCase;
use Servebolt\Optimizer\Crypto\Crypto;
use function Servebolt\Optimizer\Helpers\getOption;
use function Servebolt\Optimizer\Helpers\getOptionName;

class CryptoTest extends WP_UnitTestCase
{

    public function testThatCryptoOptionsWork(): void
    {
        $string = 'lorem-ipsum';
        $optionName = 'test_option';
        $fullOptionName = getOptionName($optionName);

        add_filter('pre_update_option_' . $fullOptionName, ['\\Servebolt\\Optimizer\\Crypto\\OptionEncryption', 'encryptOption'], 10, 1);

        $encryptedString = Crypto::encrypt($string);
        update_option($fullOptionName, $string);

        $this->assertEquals($encryptedString, get_option($fullOptionName));
        $this->assertEquals($encryptedString, getOption($optionName));

        add_filter('sb_optimizer_get_option_' . $fullOptionName, ['\\Servebolt\\Optimizer\\Crypto\\OptionEncryption', 'decryptOption'], 10, 1);
        $this->assertEquals($string, getOption($optionName));
    }

    public function testThatCryptoClassWorks(): void
    {
        $string = 'lorem-ipsum';
        $encrypted = Crypto::encrypt($string);
        $this->assertNotFalse($encrypted);
        $decrypted = Crypto::decrypt($encrypted);
        $this->assertNotFalse($decrypted);
        $this->assertEquals($string, $decrypted);
    }
}
