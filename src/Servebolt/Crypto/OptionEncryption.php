<?php

namespace Servebolt\Optimizer\Crypto;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

//use function Servebolt\Optimizer\Helpers\booleanToString;
//use function Servebolt\Optimizer\Helpers\updateBlogOption;
//use function Servebolt\Optimizer\Helpers\getBlogOption;
use function Servebolt\Optimizer\Helpers\getOptionName;

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
			$fullOptionName = getOptionName($optionName);
			add_filter('pre_update_option_' . $fullOptionName, [__CLASS__, 'encryptOption'], 10, 1);
			add_filter('sb_optimizer_get_option_' . $fullOptionName, [__CLASS__, 'decryptOption'], 10, 1);
			if (is_multisite()) {
				add_filter('sb_optimizer_get_blog_option_' . $fullOptionName, [__CLASS__, 'decryptBlogOption'], 10, 2);
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
			var_dump('getBlogOption: ' . getBlogOption($x, $key));
			var_dump('updateBlogOption: ' . booleanToString( updateBlogOption($x, $key, $this_value) ));
			var_dump('get_blog_option: ' . get_blog_option($x, getOptionName($key)));
			var_dump('getBlogOption: ' . getBlogOption($x, $key));
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
	public static function encryptOption($value)
    {
		if (empty($value)) {
            return $value;
        }
		$encryptedValue = Crypto::encrypt($value, self::getCurrentBlogId());
		return $encryptedValue !== false ? $encryptedValue : $value;
	}

	/**
	 * Decrypt option value.
	 *
	 * @param $value
	 *
	 * @return bool|string
	 */
	public static function decryptOption($value)
    {
		if (empty($value)) {
            return $value;
        }
		$decryptedValue = Crypto::decrypt($value, self::getCurrentBlogId());
		return $decryptedValue !== false ? $decryptedValue : $value;
	}

	/**
	 * Decrypt option value on a specific site.
	 * @param $value
	 * @param $blogId
	 *
	 * @return bool|string
	 */
	public static function decryptBlogOption($value, $blogId)
    {
		$decryptedValue = Crypto::decrypt($value, $blogId);
		return $decryptedValue !== false ? $decryptedValue : $value;
	}

	/**
	 * Get current blog ID with non-multisite fallback.
	 *
	 * @return bool|int
	 */
	private static function getCurrentBlogId()
    {
		if (!is_multisite()) {
			return false;
		}
		return get_current_blog_id();
	}
}
