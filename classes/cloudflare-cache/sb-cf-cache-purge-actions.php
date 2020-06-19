<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Class SB_CF_Cache_Purge_Actions
 * @package Servebolt
 *
 * This class registers the WP events which purges the cache automatically.
 */
class SB_CF_Cache_Purge_Actions {

	/**
	 * SB_CF_Cache_Purge_Actions constructor.
	 */
	public function __construct() {
		$this->register_actions();
	}

	/**
	 * Register action hooks.
	 */
	private function register_actions() {

		// Check if Cloudflare cache purge feature is active
		if ( ! sb_cf_cache()->cf_is_active() ) return;

		// Should skip all automatic cache purge?
		if ( apply_filters('sb_optimizer_disable_automatic_purge', false) ) return;

		// Purge post on post update
		if ( apply_filters('sb_optimizer_automatic_purge_on_post_update', true) ) {
			add_action( 'post_updated', [$this, 'purge_post_on_update'], 99, 1 );
		}

		// Purge post on comment post
		if ( apply_filters('sb_optimizer_automatic_purge_on_comment', true) ) {
			add_action( 'comment_post', [$this, 'purge_post_on_comment'], 99, 3 );
		}

		// Purge post when comment is approved
		if ( apply_filters('sb_optimizer_automatic_purge_on_comment_approval', true) ) {
			add_action( 'transition_comment_status', [$this, 'purge_post_on_comment_approval'], 99, 3 );
		}

		// Purge post when term is edited
		if ( apply_filters('sb_optimizer_automatic_purge_on_term_edit', true) ) {
			add_action( 'edit_term', [ $this, 'purge_post_on_term_edit' ], 99, 3 );
		}

	}

	/**
	 * @param $term_id
	 * @param $tt_id
	 * @param $taxonomy
	 */
	public function purge_post_on_term_edit($term_id, $tt_id, $taxonomy) {
		$url = get_term_link($term_id, $taxonomy);
		$links = sb_paginate_links_as_array($url, 10);
		// TODO: Find number of pages
		/*
		echo '<pre>';
		print_r($links);
		die;
		*/
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
		sb_cf_cache()->purge_post($post_id);
	}

	/**
	 * Purge post cache on comment post.
	 *
	 * @param $comment_ID
	 * @param $comment_approved
	 * @param $comment_data
	 */
	public function purge_post_on_comment($comment_ID, $comment_approved, $comment_data) {
		$post_id = $this->get_post_id_from_comment($comment_data);

		// Bail on the cache purge if we could not figure out which post was commented on
		if ( ! $post_id ) return;

		// Bail on the cache purge if the comment needs to be approved first
		if ( apply_filters('sb_optimizer_prevent_cache_purge_on_unapproved_comments', true) && ! apply_filters('sb_optimizer_comment_approved_cache_purge', $comment_approved, $comment_data) ) return;

		$this->maybe_purge_post($post_id);
	}

	/**
	 * Purge post on comment approval.
	 *
	 * @param $new_status
	 * @param $old_status
	 * @param $comment_data
	 */
	public function purge_post_on_comment_approval($new_status, $old_status, $comment_data) {
		if ( $old_status != $new_status && $new_status == 'approved' ) {
			$post_id = $this->get_post_id_from_comment($comment_data);
			if ( ! $post_id ) return;
			$this->maybe_purge_post($post_id);
		}
	}

	/**
	 * Get post ID from comment data.
	 *
	 * @param $comment_data
	 *
	 * @return bool
	 */
	private function get_post_id_from_comment($comment_data) {
		$comment_data = (array) $comment_data;
		if ( ! array_key_exists('comment_post_ID', $comment_data) ) return false;
		return $comment_data['comment_post_ID'];
	}

}
new SB_CF_Cache_Purge_Actions;
