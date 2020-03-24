<?php

/**
 * Class SB_Option_Encryption.
 */
class SB_Option_Encryption {

	/**
	 * Whether option encryption is active or not.
	 *
	 * @var bool
	 */
	private $option_encryption_active = true;

	/**
	 * The option items to apply crypto to.
	 *
	 * @var array
	 */
	private $encrypted_option_items = ['cf_email', 'cf_api_key', 'cf_api_token'];

	/**
	 * SB_Option_Encryption constructor.
	 */
	public function __construct() {
		if ( ! $this->option_encryption_active ) return;
		$this->option_encryption_handling();
	}

	/**
	 * Make sure we don't store certain option items in clear text.
	 */
	private function option_encryption_handling() {
		foreach($this->encrypted_option_items as $option_name) {
			$full_option_name = sb_get_option_name($option_name);
			add_filter( 'pre_update_option_' . $full_option_name, [$this, 'encrypt_option'], 10, 1);
			add_filter( 'sb_get_option_' . $full_option_name, [$this, 'decrypt_option'], 10, 1);
		}
	}

	/**
	 * Decrypt option value.
	 *
	 * @param $value
	 *
	 * @return bool|string
	 */
	public function decrypt_option($value) {
		$decrypted_value = SB_Crypto::decrypt($value);
		return $decrypted_value !== false ? $decrypted_value : $value;
	}

	/**
	 * Encrypt option value.
	 *
	 * @param $value
	 *
	 * @return bool|string
	 */
	public function encrypt_option($value) {
		$encrypted_value = SB_Crypto::encrypt($value);
		return $encrypted_value !== false ? $encrypted_value : $value;
	}

}
new SB_Option_Encryption;
