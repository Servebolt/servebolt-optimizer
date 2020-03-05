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
		$this->setErrorMessage($message);
	}

	/**
	 * Set error message.
	 *
	 * @param $message
	 */
	private function setErrorMessage($message) {
		$this->message = $message;
	}

	/**
	 * Get error message.
	 * @return null
	 */
	public function getMessage() {
		return $this->message;
	}

}
