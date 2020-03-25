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
		add_action( 'post_updated', [$this, 'purge_post'], 99, 1 );
	}

	/**
	 * Check if we should clear cache for post that is being updated.
	 *
	 * @param $post_id
	 *
	 * @return bool|void
	 */
	private function should_purge_post_cache($post_id) {

		// Check that the post type is public
		$post_type = get_post_type($post_id);
		$post_type_object = get_post_type_object($post_type);
		if ( $post_type_object && ! $post_type_object->publicly_queryable ) return false;

		// Make sure that post is not just a draft
		$post_status = get_post_status($post_id);
		if ( in_array($post_status, ['auto-draft']) ) return false;

		return true;
	}

	/**
	 * Purge post on save.
	 *
	 * @param $post_id
	 */
	public function purge_post($post_id) {
		if ( ! $this->should_purge_post_cache($post_id) ) return;
		sb_cf()->purge_post($post_id);
	}

}
new SB_Post_Save_Action;
