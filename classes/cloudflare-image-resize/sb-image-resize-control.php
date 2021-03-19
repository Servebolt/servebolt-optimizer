<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

use function Servebolt\Optimizer\Helpers\checkboxIsChecked;
use function Servebolt\Optimizer\Helpers\updateBlogOption;
use function Servebolt\Optimizer\Helpers\getBlogOption;
use function Servebolt\Optimizer\Helpers\updateOption;
use function Servebolt\Optimizer\Helpers\getOption;

/**
 * Class SB_Image_Resize_Control
 *
 * This class handles the CF image resize feature settings (whether the feature is active or not).
 */
class SB_Image_Resize_Control {

	/**
	 * Singleton instance.
	 *
	 * @var null
	 */
	private static $instance = null;

	/**
	 * Instantiate class.
	 *
	 * @return SB_Image_Resize_Control|null
	 */
	public static function get_instance() {
		if ( self::$instance == null ) {
			self::$instance = new SB_Image_Resize_Control;
		}
		return self::$instance;
	}

	/**
	 * The option name/key we use to store the active state for the Cloudflare image resize feature.
	 *
	 * @return string
	 */
	private static function cf_resizing_active_option_key() {
		return 'cf_image_resizing';
	}

	/**
	 * Check if Cloudflare image resize feature is active.
	 *
	 * @param bool $state
	 * @param bool $blog_id
	 *
	 * @return bool
	 */
	public function cf_image_resize_toggle_active(bool $state, $blog_id = false) {
		if ( is_numeric($blog_id) ) {
			return updateBlogOption($blog_id, $this->cf_resizing_active_option_key(), $state);
		} else {
			return updateOption($this->cf_resizing_active_option_key(), $state);
		}
	}

	/**
	 * Check if Cloudflare image resize feature is active.
	 *
	 * @param bool $blog_id
	 *
	 * @return bool
	 */
	public function resizing_is_active($blog_id = false) {
		if ( is_numeric($blog_id) ) {
			return checkboxIsChecked(getBlogOption($blog_id, $this->cf_resizing_active_option_key()));
		} else {
			return checkboxIsChecked(getOption($this->cf_resizing_active_option_key()));
		}
	}

}
