<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Class Cloudflare_Error
 */
class Cloudflare_Error {

	/**
	 * Error message.
	 *
	 * @var null
	 */
	private $message = null;

	/**
	 * Cloudflare_Error constructor.
	 *
	 * @param $message
	 */
	public function __construct($message) {
		$this->set_message($message);
	}

	/**
	 * Set error message.
	 *
	 * @param $message
	 */
	private function set_message($message) {
		$this->message = $message;
	}

	/**
	 * Get error message.
	 * @return null
	 */
	public function get_message() {
		return $this->message;
	}

}
