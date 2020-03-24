<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Class SB_Post_Save_Action
 * @package Servebolt
 *
 * This class registers the save event which purges cache either immediately or adds it the post to a cache purge queue.
 */
class SB_Post_Save_Action {

	/**
	 * SB_Post_Save_Action constructor.
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
new SB_Post_Save_Action;
