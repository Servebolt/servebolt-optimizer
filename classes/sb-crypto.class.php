<?php

/**
 * Class SB_Crypto
 */
class SB_Crypto {

    /**
     * Blog id - used to retrieve encryption keys for the given blog.
     *
     * @var bool
     */
    private static $blog_id = false;

    /**
     * Determine if and which encryption method is available.
     *
     * @return bool|string
     */
    private static function determine_encryption_method() {
        if ( function_exists('openssl_encrypt') && function_exists('openssl_decrypt') ) {
            return 'openssl';
        }
        if ( function_exists('mcrypt_encrypt') && function_exists('mcrypt_decrypt') ) {
            return 'mcrypt';
        }
        return false;
    }

    /**
     * Encrypt string.
     *
     * @param $input_string
     * @param bool $method
     * @param bool $blog_id
     *
     * @return bool|string
     */
    public static function encrypt($input_string, $blog_id = false, $method = false) {
        if ( is_multisite() && is_numeric($blog_id) ) {
            self::$blog_id = $blog_id;
        }
        try {
            if ( ! $method ) {
                $method = self::determine_encryption_method();
            }
            switch ( $method ) {
                case 'openssl':
                    return self::openssl_encrypt($input_string);
                    break;
                case 'mcrypt':
                    return self::mcrypt_encrypt($input_string);
                    break;
            }
        } catch (Exception $e) {
            return false;
        }
        return false;
    }

    /**
     * Decrypt string.
     *
     * @param $input_string

     * @param bool $blog_id
     * @param bool $method
     *
     * @return bool|string
     */
    public static function decrypt($input_string, $blog_id = false, $method = false) {
        if ( is_multisite() && is_numeric($blog_id) ) {
            self::$blog_id = $blog_id;
        }
        try {
            if ( ! $method ) {
                $method = self::determine_encryption_method();
            }
            switch ( $method ) {
                case 'openssl':
                    return self::openssl_decrypt($input_string);
                    break;
                case 'mcrypt':
                    return self::mcrypt_decrypt($input_string);
                    break;
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
    public static function mcrypt_key() {
        $key = sb_generate_random_permanent_key('mcrypt_key', self::$blog_id);
        $key = apply_filters('sb_optimizer_mcrypt_key', $key);
        return $key;
    }

    /**
     * Initiate mcrypt encryption/decryption.
     *
     * @return array
     */
    public static function mcrypt_init() {
        $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
        $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
        $h_key = hash('sha256', self::mcrypt_key(), TRUE);
        return compact('iv', 'h_key');
    }

    /**
     * Encrypt string using mcrypt.
     *
     * @param $input_string
     *
     * @return string
     */
    public static function mcrypt_encrypt($input_string) {
        $init = self::mcrypt_init();
        return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $init['h_key'], $input_string, MCRYPT_MODE_ECB, $init['iv']));
    }

    /**
     * Decrypt string using mcrypt.
     *
     * @param $encrypted_input_string
     *
     * @return string
     */
    public static function mcrypt_decrypt($encrypted_input_string) {
        $init = self::mcrypt_init();
        return trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $init['h_key'], base64_decode($encrypted_input_string), MCRYPT_MODE_ECB, $init['iv']));
    }

    /**
     * OpenSSL encryption keys.
     *
     * @return array
     */
    public static function openssl_keys() {
        $key = sb_generate_random_permanent_key('openssl_key', self::$blog_id);
        $iv = sb_generate_random_permanent_key('openssl_iv', self::$blog_id);
        $keys = apply_filters('sb_optimizer_openssl_keys', compact('key', 'iv'));
        return $keys;
    }

    /**
     * Init OpenSSL.
     *
     * @return array
     */
    public static function openssl_init() {
        $encrypt_method = 'AES-256-CBC';
        $secret = self::openssl_keys();
        $key = hash('sha256', $secret['key']);
        $iv = substr(hash('sha256', $secret['iv']), 0, 16);
        return compact('encrypt_method', 'key', 'iv');
    }

    /**
     * Encrypt string using mcrypt.
     *
     * @param $input_string
     *
     * @return string
     */
    public static function openssl_encrypt($input_string) {
        $init = self::openssl_init();
        return base64_encode(openssl_encrypt($input_string, $init['encrypt_method'], $init['key'], 0, $init['iv']));
    }

    /**
     * Decrypt string using OpenSSL.
     *
     * @param $encrypted_input_string
     *
     * @return string
     */
    public static function openssl_decrypt($encrypted_input_string) {
        $init = self::openssl_init();
        return openssl_decrypt(base64_decode($encrypted_input_string), $init['encrypt_method'], $init['key'], 0, $init['iv']);
    }

}
