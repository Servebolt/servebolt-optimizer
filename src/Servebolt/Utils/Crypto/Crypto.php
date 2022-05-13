<?php

namespace Servebolt\Optimizer\Utils\Crypto;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Throwable;
use function Servebolt\Optimizer\Helpers\generateRandomPermanentKey;

/**
 * Class Crypto
 */
class Crypto
{

    /**
     * Blog id - used to retrieve encryption keys for the given blog.
     *
     * @var int|bool
     */
    private static $blogId = false;

    /**
     * Determine if and which encryption method is available.
     *
     * @return bool|string
     */
    private static function determineEncryptionMethod()
    {
        if (function_exists('openssl_encrypt') && function_exists('openssl_decrypt')) {
            return 'openssl';
        }
        if (function_exists('mcrypt_encrypt') && function_exists('mcrypt_decrypt')) {
            return 'mcrypt';
        }
        return false;
    }

    /**
     * Encrypt string.
     *
     * @param string|null $inputString
     * @param int|bool|string $blogId
     * @param bool $method
     *
     * @return bool|string
     */
    public static function encrypt(?string $inputString, $blogId = false, $method = false)
    {
        if (is_multisite() && (is_numeric($blogId) || $blogId === 'site')) {
            self::$blogId = $blogId;
        }
        if (empty($inputString)) {
            return $inputString;
        }
        try {
            if (!$method) {
                $method = self::determineEncryptionMethod();
            }
            switch ($method) {
                case 'openssl':
                    return self::opensslEncrypt($inputString);
                case 'mcrypt':
                    return self::mcryptEncrypt($inputString);
            }
        } catch (Throwable $e) {
            return false;
        }
        return false;
    }

    /**
     * Decrypt string.
     *
     * @param string|null $inputString
     * @param int|bool|string $blogId
     * @param bool|string $method
     *
     * @return bool|string
     */
    public static function decrypt(?string $inputString, $blogId = false, $method = false)
    {
        if (is_multisite() && (is_numeric($blogId) || $blogId === 'site')) {
            self::$blogId = $blogId;
        }
        if (empty($inputString)) {
            return $inputString;
        }
        try {
            if (!$method) {
                $method = self::determineEncryptionMethod();
            }
            switch ($method) {
                case 'openssl':
                    return self::opensslDecrypt($inputString);
                case 'mcrypt':
                    return self::mcryptDecrypt($inputString);
            }
        } catch (Exception $e) {
            return false;
        }
        return false;
    }

    /**
     * Mcrypt key.
     *
     * @return string
     */
    public static function mcryptKey(): string
    {
        $key = generateRandomPermanentKey('mcrypt_key', self::$blogId);
        $key = apply_filters('sb_optimizer_mcrypt_key', $key);
        return $key;
    }

    /**
     * Initiate mcrypt encryption/decryption.
     *
     * @return array
     */
    public static function mcryptInit(): array
    {
        $ivSize = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
        $iv = mcrypt_create_iv($ivSize, MCRYPT_RAND);
        $hKey = hash('sha256', self::mcryptKey(), true);
        return compact('iv', 'hKey');
    }

    /**
     * Encrypt string using mcrypt.
     *
     * @param string $inputString
     *
     * @return string
     */
    public static function mcryptEncrypt(string $inputString): string
    {
        $init = self::mcryptInit();
        return base64_encode(mcrypt_encrypt(
            MCRYPT_RIJNDAEL_256,
            $init['hKey'],
            $inputString,
            MCRYPT_MODE_ECB,
            $init['iv']
        ));
    }

    /**
     * Decrypt string using mcrypt.
     *
     * @param string $encryptedInputString
     *
     * @return string
     */
    public static function mcryptDecrypt(string $encryptedInputString): string
    {
        $init = self::mcryptInit();
        return trim(mcrypt_decrypt(
            MCRYPT_RIJNDAEL_256,
            $init['hKey'],
            base64_decode($encryptedInputString),
            MCRYPT_MODE_ECB,
            $init['iv']
        ));
    }

    /**
     * OpenSSL encryption keys.
     *
     * @return array
     */
    public static function opensslKeys(): array
    {
        $key = generateRandomPermanentKey('openssl_key', self::$blogId);
        $iv = generateRandomPermanentKey('openssl_iv', self::$blogId);
        return apply_filters('sb_optimizer_openssl_keys', compact('key', 'iv'));
    }

    /**
     * Init OpenSSL.
     *
     * @return array
     */
    public static function opensslInit(): array
    {
        $encryptMethod = 'AES-256-CBC';
        $secret = self::opensslKeys();
        $key = hash('sha256', $secret['key']);
        $iv = substr(hash('sha256', $secret['iv']), 0, 16);
        return compact('encryptMethod', 'key', 'iv');
    }

    /**
     * Encrypt string using mcrypt.
     *
     * @param string $inputString
     *
     * @return string
     */
    public static function opensslEncrypt(string $inputString): string
    {
        $init = self::opensslInit();
        return base64_encode(
            openssl_encrypt(
                $inputString,
                $init['encryptMethod'],
                $init['key'],
                0,
                $init['iv']
            )
        );
    }

    /**
     * Decrypt string using OpenSSL.
     *
     * @param string $encryptedInputString
     *
     * @return string
     */
    public static function opensslDecrypt(string $encryptedInputString)
    {
        $init = self::opensslInit();
        return openssl_decrypt(
            base64_decode($encryptedInputString),
            $init['encryptMethod'],
            $init['key'],
            0,
            $init['iv']
        );
    }

}
