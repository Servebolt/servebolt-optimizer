<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Class WP_CLI_FPC_Extra
 *
 * This class contains the FPC-related helper methods when using the WP CLI.
 */
abstract class Servebolt_CLI_FPC_Extra extends Servebolt_CLI_Extras {

	/**
	 * Display Nginx status - which post types have cache active.
	 *
	 * @param bool $blog_id
	 *
	 * @return array
	 */
	protected function get_nginx_fpc_status($blog_id = false) {
		$status = sb_nginx_fpc()->fpc_is_active($blog_id) ? 'Active' : 'Inactive';
		$post_types = sb_nginx_fpc()->get_post_types_to_cache(true, true, $blog_id);
		$enabled_post_types_string = $this->nginx_get_active_post_types_string($post_types);
		$excluded_posts = sb_nginx_fpc()->get_ids_to_exclude_from_cache($blog_id);
		$array = [];
		if ( $blog_id ) {
			$array['URL'] = get_site_url($blog_id);
		}
		$array = array_merge($array, [
			'Status'            => $status,
			'Active post types' => $enabled_post_types_string,
			'Posts to exclude'  => sb_format_array_to_csv($excluded_posts),
		]);
		return $array;
	}

	/**
	 * Get post types to cache with FPC.
	 *
	 * @param bool $blog_id
	 *
	 * @return array
	 */
	protected function nginx_fpc_get_cache_post_types($blog_id = false) {
		$post_types = sb_nginx_fpc()->get_post_types_to_cache(true, true, $blog_id);
		$enabled_post_types_string = $this->nginx_get_active_post_types_string($post_types);
		$array = [];
		if ( $blog_id ) {
			$array['URL'] = get_site_url($blog_id);
		}
		$array = array_merge($array, [
			'Active post types' => $enabled_post_types_string,
		]);
		return $array;
	}

	/**
	 * Get the string displaying which post types are active in regards to Nginx caching.
	 *
	 * @param $post_types
	 *
	 * @return string|void
	 */
	private function nginx_get_active_post_types_string($post_types) {

		// Cache default post types
		if ( ! is_array($post_types) || empty($post_types) ) {
			return sprintf( sb__( 'Default [%s]' ), sb_nginx_fpc()->get_default_post_types_to_cache( 'csv' ) );
		}

		// Cache all post types
		if ( array_key_exists('all', $post_types) ) {
			return sb__( 'All' );
		}
		return sb_format_array_to_csv($post_types);
	}

	/**
	 * Toggle cache active/inactive for site.
	 *
	 * @param $new_cache_state
	 * @param null $blog_id
	 */
	private function nginx_toggle_cache_for_blog($new_cache_state, $blog_id = null) {
		$url = get_site_url($blog_id);
		$cache_active_string = sb_boolean_to_state_string($new_cache_state);
		if ( $new_cache_state === sb_nginx_fpc()->fpc_is_active($blog_id) ) {
			WP_CLI::warning(sprintf( sb__( 'Full Page Cache already %s on site %s' ), $cache_active_string, $url ));
		} else {
			sb_nginx_fpc()->fpc_toggle_active($new_cache_state, $blog_id);
			WP_CLI::success(sprintf( sb__( 'Full Page Cache %s on site %s' ), $cache_active_string, $url ));
		}
	}

	/**
	 * Prepare post types from argument if present. To be used when toggling Nginx FPC active/inactive.
	 *
	 * @param $args
	 *
	 * @return array|bool
	 */
	private function nginx_prepare_post_type_argument($args) {
		if ( array_key_exists('post-types', $args) ) {
			$post_types = sb_format_comma_string($args['post-types']);
			$post_types = array_filter($post_types, function ($post_type) {
				return post_type_exists($post_type);
			});
			if ( ! empty($post_types) ) {
				return $post_types;
			}
		}
		return false;
	}

	/**
	 * Enable/disable Nginx cache headers.
	 *
	 * @param bool $cache_active
	 * @param array $args
	 */
	protected function nginx_fpc_control($cache_active, $args = []){

		$affect_all_blogs    = array_key_exists('all', $args);
		$post_types          = $this->nginx_prepare_post_type_argument($args);
		$exclude_ids         = sb_array_get('exclude', $args);

		if ( is_multisite() && $affect_all_blogs ) {
			WP_CLI::line(sb__('Applying settings to all blogs'));
			sb_iterate_sites(function($site) use ($cache_active, $post_types) {
				$this->nginx_toggle_cache_for_blog($cache_active, $site->blog_id);
				if ( $post_types ) $this->nginx_set_post_types($post_types, $site->blog_id);
			});
			if ( $exclude_ids ) {
				WP_CLI::warning(sb__('Exclude ids were not set since ids are relative to each site.'));
			}
		} else {
			$this->nginx_toggle_cache_for_blog($cache_active);
			if ( $post_types ) $this->nginx_set_post_types($post_types);
			if ( $exclude_ids ) $this->nginx_set_exclude_ids($exclude_ids);
		}
	}

	/**
	 * Set post types to cache.
	 *
	 * @param $post_types
	 * @param bool $blog_id
	 */
	protected function nginx_set_post_types($post_types, $blog_id = false) {
		if ( $post_types === false ) $post_types = [];
		$all_post_types = $this->nginx_get_all_post_types();
		$all_post_type_keys = array_keys($all_post_types);
		$post_types = array_filter($post_types, function($post_type) use ($all_post_type_keys) {
			return in_array($post_type, $all_post_type_keys) || $post_type === 'all';
		});
		sb_nginx_fpc()->set_cacheable_post_types($post_types, $blog_id);
		if ( empty($post_types) ) {
			if ( $blog_id ) {
				WP_CLI::success(sprintf(sb__('Cache post type(s) cleared on site %s'), get_site_url($blog_id)));
			} else {
				WP_CLI::success(sprintf(sb__('Cache post type(s) cleared'), get_site_url($blog_id)));
			}
		} else {
			if ( $blog_id ) {
				WP_CLI::success(sprintf(sb__('Cache post type(s) set to %s on site %s'), sb_format_array_to_csv($post_types), get_site_url($blog_id)));
			} else {
				WP_CLI::success(sprintf(sb__('Cache post type(s) set to %s'), sb_format_array_to_csv($post_types)));
			}
		}
	}

	/**
	 * Get all post types to be used in Nginx FPC context.
	 *
	 * @return array|bool
	 */
	private function nginx_get_all_post_types() {
		$all_post_types = sb_nginx_fpc()->get_available_post_types_to_cache(false);
		if ( is_array($all_post_types) ) {
			return array_map(function($post_type) {
				return isset($post_type->name) ? $post_type->name : $post_type;
			}, $all_post_types);
		}
		return false;
	}

	/**
	 * Set posts to be excluded from the Nginx FPC.
	 *
	 * @param $ids_to_exclude
	 * @param bool $blog_id
	 */
	protected function nginx_set_exclude_ids($ids_to_exclude, $blog_id = false) {
		if ( is_string($ids_to_exclude) ) {
			$ids_to_exclude = sb_format_comma_string($ids_to_exclude);
		}
		$already_excluded = sb_nginx_fpc()->get_ids_to_exclude_from_cache($blog_id);
		$clear_all = $ids_to_exclude === false;

		if ( $clear_all ) {
			sb_nginx_fpc()->set_ids_to_exclude_from_cache([], $blog_id);
			WP_CLI::success(sb__('All excluded posts were cleared.'));
			return;
		} elseif ( is_array($ids_to_exclude) && empty($ids_to_exclude) ) {
			WP_CLI::warning(sb__('No ids were specified.'));
			return;
		}

		$already_added = [];
		$was_excluded = [];
		$invalid_id = [];
		foreach ($ids_to_exclude as $id) {
			if ( get_post_status( $id ) === false ) {
				$invalid_id[] = $id;
			} elseif ( ! in_array($id, $already_excluded)) {
				$was_excluded[] = $id;
				$already_excluded[] = $id;
			} else {
				$already_added[] = $id;
			}
		}
		sb_nginx_fpc()->set_ids_to_exclude_from_cache($already_excluded, $blog_id);

		if ( ! empty($already_added) ) {
			WP_CLI::warning(sprintf(sb__('The following ids were already excluded: %s'), sb_format_array_to_csv($already_added)));
		}

		if ( ! empty($invalid_id) ) {
			WP_CLI::warning(sprintf(sb__('The following ids were invalid: %s'), sb_format_array_to_csv($invalid_id)));
		}

		if ( ! empty($was_excluded) ) {
			WP_CLI::success(sprintf(sb__('Added %s to the list of excluded ids'), sb_format_array_to_csv($was_excluded)));
		} else {
			WP_CLI::warning(sb__('No action was made.'));
		}
	}

	/**
	 * Get posts excluded from FPC for specified site.
	 *
	 * @param bool $blog_id
	 * @param bool $extended
	 *
	 * @return array
	 */
	protected function nginx_fpc_get_excluded_posts($blog_id = false, $extended = false) {
		if ( $blog_id ) {
			$arr = ['Blog' => $blog_id];
		} else {
			$arr = [];
		}
		$already_excluded = sb_nginx_fpc()->get_ids_to_exclude_from_cache($blog_id);
		if ( $extended ) {
			$already_excluded = sb_format_array_to_csv(sb_resolve_post_ids($already_excluded), ', ');
		} else {
			$already_excluded = sb_format_array_to_csv($already_excluded);
		}
		$arr['Excluded posts'] = $already_excluded;
		return $arr;
	}

}
