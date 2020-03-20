<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Class CF_Cache_Action
 * @package Servebolt
 *
 * This class registers the save event to queue/purge cache.
 */
class CF_Cache_Action {

	/**
	 * CF_Cache_Controls constructor.
	 */
	public function __construct() {
		$this->register_actions();
	}

	/**
	 * Register action hooks.
	 */
	private function register_actions() {
		if ( ! sb_cf()->cf_is_active() ) return;
		add_action( 'save_post', [sb_cf(), 'purge_post'], 99, 1 );
	}
}
