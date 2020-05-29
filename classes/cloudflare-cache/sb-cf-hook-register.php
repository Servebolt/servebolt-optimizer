<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Class SB_Post_Save_Action
 * @package Servebolt
 *
 * This class registers the WP event which purges cache.
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
		add_action( 'post_updated', [$this, 'purge_post_on_update'], 99, 1 );
		//add_action( 'comment_post', [$this, 'purge_post_on_comment'], 99, 3 );
		//add_action( 'transition_comment_status', [$this, 'purge_post_on_comment_approval'], 99, 3 );
	}

	/**
	 * Check if we should clear cache for post that is being updated.
	 *
	 * @param $post_id
	 *
	 * @return bool|void
	 */
	private function should_purge_post_cache($post_id) {

		// Let users override the outcome
		$override = apply_filters('sb_optimizer_should_purge_post_cache', null, $post_id);
		if ( is_bool($override) ) return $override;

		// Check that the post type is public
		$post_type = get_post_type($post_id);
		$post_type_object = get_post_type_object($post_type);
		if ( ! $post_type_object || $post_type_object->public === true || $post_type_object->publicly_queryable === true ) return true;

		// Make sure that post is not just a draft
		$post_status = get_post_status($post_id);
		if ( in_array($post_status, ['publish']) ) return true;

		return false;
	}

	/**
	 * Purge post on post save.
	 *
	 * @param $post_id
	 */
	public function purge_post_on_update($post_id) {
		$this->maybe_purge_post($post_id);

	}

	/**
	 * Maybe purge post by post ID.
	 *
	 * @param $post_id
	 */
	private function maybe_purge_post($post_id) {
		if ( ! $this->should_purge_post_cache($post_id) ) return;
		sb_cf()->purge_post($post_id);
	}

	/**
	 * Purge post cache on comment post.
	 *
	 * @param $comment_ID
	 * @param $comment_approved
	 * @param $commentdata
	 */
	public function purge_post_on_comment($comment_ID, $comment_approved, $commentdata) {
		$post_id = $this->get_post_id_from_comment($commentdata);
		if ( ! $post_id ) return;
		if ( ! $this->should_purge_post_cache($post_id) ) return;
		sb_cf()->purge_post($post_id);
	}

	/**
	 * Purge post on comment approval.
	 *
	 * @param $new_status
	 * @param $old_status
	 * @param $comment
	 */
	public function purge_post_on_comment_approval($new_status, $old_status, $comment) {
		if( $old_status != $new_status ) {
			if ( $new_status == 'approved' ) {
				$post_id = $this->get_post_id_from_comment($comment);
				if ( ! $post_id ) return;
				$this->maybe_purge_post($post_id);
			}
		}
	}

	/**
	 * Get post ID from comment data.
	 *
	 * @param $comment
	 *
	 * @return bool
	 */
	private function get_post_id_from_comment($comment) {
		if ( ! array_key_exists('comment_post_ID', $comment) ) return false;
		return $comment['comment_post_ID'];
	}

}
new SB_Post_Save_Action;
