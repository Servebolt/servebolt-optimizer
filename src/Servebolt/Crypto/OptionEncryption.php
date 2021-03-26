<?php

namespace Servebolt\Optimizer\Crypto;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

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
	 * @var array The option items to apply crypto to.
	 */
	private $encryptedOptionItems = ['cf_email', 'cf_api_key', 'cf_api_token'];

    /**
     * @var array The site option items to apply crypto to.
     */
	private $encryptedSiteOptionItems = [];

	/**
	 * SB_Option_Encryption constructor.
	 */
	public function __construct() {
		if ($this->optionEncryptionActive) {
            $this->optionEncryptionHandling();
            $this->siteOptionEncryptionHandling();
        }
	}

    /**
     * Make sure we don't store certain option items in clear text.
     */
	private function siteOptionEncryptionHandling(): void
    {
        foreach($this->encryptedSiteOptionItems() as $optionName) {
            if (is_multisite()) {
                $fullOptionName = getOptionName($optionName);
                add_filter('pre_update_site_option_' . $fullOptionName, [$this, 'encryptSiteOption'], 10, 2);
                add_filter('sb_optimizer_get_site_option_' . $fullOptionName, [$this, 'decryptSiteOption'], 10, 2);
            }
        }
    }

	/**
	 * Make sure we don't store certain option items in clear text.
	 */
	private function optionEncryptionHandling(): void
    {
		foreach($this->encryptedOptionItems() as $optionName) {
			$fullOptionName = getOptionName($optionName);

			// Handles both multisite and single site options
			add_filter('pre_update_option_' . $fullOptionName, [$this, 'encryptOption'], 10, 1);

			// Single site option
			add_filter('sb_optimizer_get_option_' . $fullOptionName, [$this, 'decryptOption'], 10, 1);

			if (is_multisite()) {
				// Blog option
				add_filter('sb_optimizer_get_blog_option_' . $fullOptionName, [$this, 'decryptBlogOption'], 10, 2);
			}
		}
	}

    private function encryptedSiteOptionItems(): array
    {
        $this->encryptedSiteOptionItems[] = 'sb-test-options-key';
        return $this->encryptedSiteOptionItems;
    }

    private function encryptedOptionItems(): array
    {
        $this->encryptedOptionItems[] = 'sb-test-options-key';
        return $this->encryptedOptionItems;
    }

    /**
     * Encrypt site option value.
     *
     * @param $value
     *
     * @return bool|string
     */
    public function encryptSiteOption($value)
    {
        if (empty($value)) {
            return $value;
        }
        $encryptedValue = Crypto::encrypt($value, 'site');
        return $encryptedValue !== false ? $encryptedValue : $value;
    }

    /**
     * Decrypt site option value.
     *
     * @param $value
     *
     * @return bool|string
     */
    public function decryptSiteOption($value)
    {
        if (empty($value)) {
            return $value;
        }
        $decryptedValue = Crypto::decrypt($value, 'site');
        return $decryptedValue !== false ? $decryptedValue : $value;
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
     *
	 * @param $value
	 * @param $blogId
	 *
	 * @return bool|string
	 */
	public function decryptBlogOption($value, $blogId)
    {
		$decryptedValue = Crypto::decrypt($value, $blogId);
		return $decryptedValue !== false ? $decryptedValue : $value;
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
