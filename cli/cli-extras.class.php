<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Class Servebolt_CLI_Extras
 * @package Servebolt
 *
 * Additional methods for CLI-class.
 */
class Servebolt_CLI_Extras {

	/**
	 * Store Cloudflare zones for cache purposes.
	 *
	 * @var null
	 */
	protected $zones = null;

	/**
	 * Get Cloudflare zones.
	 *
	 * @return |null
	 */
	protected function get_zones() {
		if ( is_null($this->zones) ) {
			$this->zones = sb_cf()->list_zones();
		}
		return $this->zones;
	}

	/**
	 * List Cloudflare zones with/without numbering.
	 *
	 * @param bool $includeNumbers
	 */
	protected function list_zones($includeNumbers = false) {
		$zones = $this->get_zones();
		if ( ! $zones || empty($zones) ) {
			WP_CLI::error('Could not retrieve any available zones. Make sure you have configured the Cloudflare API credentials and set an active zone.');
		}
		WP_CLI::line('The following zones are available:');
		foreach ($zones as $i => $zone ) {
			if ( $includeNumbers === true ) {
				WP_CLI::line(sprintf('[%s] %s (%s)', $i+1, $zone->name, $zone->id));
			} else {
				WP_CLI::line(sprintf('%s (%s)', $zone->name, $zone->id));
			}
		}
	}

	/**
	 * Display Nginx status - which post types have cache active.
	 *
	 * @param $assoc_args
	 */
	protected function get_nginx_status($assoc_args) {
		if ( is_multisite() && array_key_exists('all', $assoc_args) ) {
			$sites = get_sites();
			$sites_status = [];
			foreach ( $sites as $site ) {
				$status = sb_nginx_fpc()->fpc_is_active($site->blog_id) ? 'Active' : 'Inactive';
				$post_types = sb_nginx_fpc()->get_cacheable_post_types(true, $site->blog_id);
				$enabled_post_types_string = $this->nginx_get_active_post_types_string($post_types);
				$sites_status[] = [
					'URL'               => get_site_url($site->blog_id),
					'STATUS'            => $status,
					'ACTIVE_POST_TYPES' => $enabled_post_types_string,
				];
			}
			WP_CLI\Utils\format_items( 'table', $sites_status , array_keys(current($sites_status)));
		} else {
			$status = sb_nginx_fpc()->fpc_is_active() ? 'activate' : 'inactive';
			$post_types = sb_nginx_fpc()->get_cacheable_post_types();
			$enabled_post_types_string = $this->nginx_get_active_post_types_string($post_types);
			WP_CLI::line( sprintf( sb__( 'Servebolt Full Page Cache cache is %s' ), $status ) );
			WP_CLI::line( sprintf( sb__( 'Post types enabled for caching: %s' ), $enabled_post_types_string ) );
		}
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
			return sprintf( sb__( 'Default [%s]' ), sb_nginx_fpc()->default_cacheable_post_types( 'csv' ) );
		}

		// Cache all post types
		if ( array_key_exists('all', $post_types) ) {
			return sb__( 'All' );
		}

		$enabled_post_types = [];
		foreach ( $post_types as $key => $value ) {
			if ( $value ) {
				$enabled_post_types[] = $key;
			}
		}
		return implode(',', $enabled_post_types);
	}

	/**
	 * Toggle cachce active/inactive for blog.
	 *
	 * @param $new_cache_state
	 * @param null $blog_id
	 */
	private function nginx_toggle_cache_for_blog($new_cache_state, $blog_id = null) {
		$url = get_site_url($blog_id);
		$cache_active_string = sb_boolean_to_state_string($new_cache_state);
		if ( $new_cache_state === sb_nginx_fpc()->fpc_is_active($blog_id) ) {
			WP_CLI::warning( sprintf( sb__( 'Full Page Cache already %s on %s' ), $cache_active_string, $url ) );
		} else {
			sb_nginx_fpc()->fpc_toggle_active($new_cache_state, $blog_id);
			WP_CLI::success( sprintf( sb__( 'Full Page Cache %s on %s' ), $cache_active_string, $url ) );
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
		if ( array_key_exists('post_types', $args) ) {
			$post_types = $this->format_comma_string();
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
	protected function nginx_control($cache_active, $args){

		$affect_all_blogs    = array_key_exists('all', $args);
		$post_types          = $this->nginx_prepare_post_type_argument($args);
		$exclude_ids         = array_key_exists('exclude', $args) ? $args['exclude'] : false;

		if ( is_multisite() && $affect_all_blogs ) {
			WP_CLI::info( sb__('Aplying settings to all blogs') );
			foreach (get_sites() as $site) {
				$this->nginx_toggle_cache_for_blog($cache_active, $site->blog_id);
				if ( $post_types ) $this->nginx_set_post_types($post_types, $cache_active, $site->blog_id);
				if ( $exclude_ids ) $this->nginx_set_exclude_ids($exclude_ids, $site->blog_id);
			}
		} else {
			$this->nginx_toggle_cache_for_blog($cache_active);
			if ( $post_types ) $this->nginx_set_post_types($post_types, $cache_active);
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
		$cache_all_post_types = in_array('all', $post_types);
		$all_post_types = $this->nginx_get_all_post_types($blog_id);// TODO: This needs to respect the post types of each blog in a multisite context (doenst it?)
		$url = get_site_url($blog_id);

		if ( $cache_all_post_types ) {
			$post_types = $all_post_types;
		} else {
			$post_types = array_filter($post_types, function($post_type) use ($all_post_types) {
				return in_array($post_type, $all_post_types) || $post_type === 'all';
			});
		}
		sb_nginx_fpc()->set_cacheable_post_types($post_types, $blog_id);
		WP_CLI::success(sprintf(sb__('Cache post type(s) set to %s on %s'), implode(', ', $post_types), $url));
	}

	/**
	 * Get all post types to be used in cache context.
	 *
	 * @param bool $blog_id
	 *
	 * @return array|bool
	 */
	private function nginx_get_all_post_types($blog_id = false) {
		// TODO: Should the "get_post_types"-function respect $blog_id?
		$all_post_types = get_post_types(['public' => true], 'objects');
		if ( is_array($all_post_types) ) {
			return array_map(function($post_type) {
				return $post_type->name;
			}, $all_post_types);
		}
		return false;
	}

	/**
	 * Set exclude Ids.
	 *
	 * @param $ids_to_exclude_string
	 */
	protected function nginx_set_exclude_ids($ids_to_exclude_string) {

		$ids_to_exclude = $this->format_comma_string($ids_to_exclude_string);
		$already_excluded = sb_nginx_fpc()->get_ids_to_exclude();

		if ( empty($ids_to_exclude) ) {
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
		sb_nginx_fpc()->set_ids_to_exclude($already_excluded);

		if ( ! empty($already_added) ) {
			WP_CLI::info(sprintf(sb__('The following ids were already excluded: %s'), implode(',', $already_added)));
		}

		if ( ! empty($invalid_id) ) {
			WP_CLI::warning(sprintf(sb__('The following ids were invalid: %s'), implode(',', $already_added)));
		}

		if ( ! empty($was_excluded) ) {
			WP_CLI::success(sprintf(sb__('Added %s to the list of excluded ids'), implode(',', $was_excluded)));
		} else {
			WP_CLI::info(sb__('No action was made.'));
		}
	}

	/**
	 * Format a string with comma separated values.
	 *
	 * @param $string Comma separated values.
	 *
	 * @return array
	 */
	private function format_comma_string($string) {
		$array = explode(',', $string);
		$array = array_map(function ($item) {
			return trim($item);
		}, $array);
		return array_filter($array, function ($item) {
			return ! empty($item);
		});
	}

}
