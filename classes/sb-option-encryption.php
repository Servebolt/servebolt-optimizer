<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Class SB_Option_Encryption.
 *
 * This class is used to prevent clear-text storage of strings in the options-table.
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
			add_filter( 'sb_optimizer_get_option_' . $full_option_name, [$this, 'decrypt_option'], 10, 1);
			if ( is_multisite() ) {
				add_filter( 'sb_optimizer_get_blog_option_' . $full_option_name, [$this, 'decrypt_blog_option'], 10, 2);
			}
		}

		// Multisite options encryption debugging
		/*
		$value = 'hallo';
		$key = 'test10';
		var_dump('enc str: ' . $value);
		var_dump('enc key: ' . $key);
		$x = 1;
		while($x <= 3) {
			$this_value = $value . ' ' . $x;
			echo '---' . PHP_EOL;
			var_dump('blog id: ' . $x);
			var_dump('sb_get_blog_option: ' . sb_get_blog_option($x, $key));
			var_dump('sb_update_blog_option: ' . ( sb_update_blog_option($x, $key, $this_value) ? 'true' : 'false'));
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
	public function encrypt_option($value) {
		if ( empty($value) ) return $value;
		$encrypted_value = SB_Crypto::encrypt($value, $this->get_current_blog_id());
		return $encrypted_value !== false ? $encrypted_value : $value;
	}

	/**
	 * Decrypt option value.
	 *
	 * @param $value
	 *
	 * @return bool|string
	 */
	public function decrypt_option($value) {
		if ( empty($value) ) return $value;
		$decrypted_value = SB_Crypto::decrypt($value, $this->get_current_blog_id());
		return $decrypted_value !== false ? $decrypted_value : $value;
	}

	/**
	 * Decrypt option value on a specific site.
	 * @param $value
	 * @param $blog_id
	 *
	 * @return bool|string
	 */
	public function decrypt_blog_option($value, $blog_id) {
		$decrypted_value = SB_Crypto::decrypt($value, $blog_id);
		return $decrypted_value !== false ? $decrypted_value : $value;
	}

	/**
	 * Get current blog ID with non-multisite fallback.
	 *
	 * @return bool
	 */
	private function get_current_blog_id() {
		if ( ! is_multisite() ) {
			return false;
		}
		return get_current_blog_id();
	}

}
new SB_Option_Encryption;
