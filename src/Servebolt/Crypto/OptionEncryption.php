<?php

namespace Servebolt\Optimizer\Crypto;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Class SB_Option_Encryption.
 *
 * This class is used to prevent clear-text storage of strings in the options-table.
 */
class OptionEncryption
{

	/**
	 * Whether option encryption is active or not.
	 *
	 * @var bool
	 */
	private $optionEncryptionActive = true;

	/**
	 * The option items to apply crypto to.
	 *
	 * @var array
	 */
	private $encryptedOptionItems = ['cf_email', 'cf_api_key', 'cf_api_token'];

	/**
	 * SB_Option_Encryption constructor.
	 */
	public function __construct() {
		if ($this->optionEncryptionActive) {
            $this->optionEncryptionHandling();
        }
	}

	/**
	 * Make sure we don't store certain option items in clear text.
	 */
	private function optionEncryptionHandling(): void
    {
		foreach($this->encryptedOptionItems as $optionName) {
			$fullOptionName = sb_get_option_name($optionName);
			add_filter('pre_update_option_' . $fullOptionName, [$this, 'encryptOption'], 10, 1);
			add_filter('sb_optimizer_get_option_' . $fullOptionName, [$this, 'decryptOption'], 10, 1);
			if (is_multisite()) {
				add_filter('sb_optimizer_get_blog_option_' . $fullOptionName, [$this, 'decryptBlogOption'], 10, 2);
			}
		}

		// Multisite options encryption debugging
		/*
		$value = 'test-value';
		$key = 'test-key';
		var_dump('enc str: ' . $value);
		var_dump('enc key: ' . $key);
		$x = 1;
		while($x <= 3) {
			$this_value = $value . ' ' . $x;
			echo '---' . PHP_EOL;
			var_dump('blog id: ' . $x);
			var_dump('sb_get_blog_option: ' . sb_get_blog_option($x, $key));
			var_dump('sb_update_blog_option: ' . sb_boolean_to_string( sb_update_blog_option($x, $key, $this_value) ));
			var_dump('get_blog_option: ' . get_blog_option($x, sb_get_option_name($key)));
			var_dump('sb_get_blog_option: ' . sb_get_blog_option($x, $key));
			$x++;
		}
		die;
		*/

	}

	/**
	 * Encrypt option value.
	 *
	 * @param $value
	 *
	 * @return bool|string
	 */
	public function encryptOption($value)
    {
		if (empty($value)) {
            return $value;
        }
		$encryptedValue = Crypto::encrypt($value, $this->getCurrentBlogId());
		return $encryptedValue !== false ? $encryptedValue : $value;
	}

	/**
	 * Decrypt option value.
	 *
	 * @param $value
	 *
	 * @return bool|string
	 */
	public function decryptOption($value)
    {
		if (empty($value)) {
            return $value;
        }
		$decryptedValue = Crypto::decrypt($value, $this->getCurrentBlogId());
		return $decryptedValue !== false ? $decryptedValue : $value;
	}

	/**
	 * Decrypt option value on a specific site.
	 * @param $value
	 * @param $blog_id
	 *
	 * @return bool|string
	 */
	public function decryptBlogOption($value, $blog_id) {
		$decrypted_value = Crypto::decrypt($value, $blog_id);
		return $decrypted_value !== false ? $decrypted_value : $value;
	}

	/**
	 * Get current blog ID with non-multisite fallback.
	 *
	 * @return bool
	 */
	private function getCurrentBlogId()
    {
		if (!is_multisite()) {
			return false;
		}
		return get_current_blog_id();
	}
}
