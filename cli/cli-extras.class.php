<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Class Servebolt_CLI_Extras
 * @package Servebolt
 *
 * Additional methods for CLI-class.
 */
abstract class Servebolt_CLI_Extras {

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
	 * @param bool $include_numbers
	 */
	protected function list_zones($include_numbers = false) {
		$zones = $this->get_zones();
		if ( ! $zones || empty($zones) ) {
			WP_CLI::error('Could not retrieve any available zones. Make sure you have configured the Cloudflare API credentials and set an active zone.');
		}
		WP_CLI::line('The following zones are available:');
		foreach ($zones as $i => $zone ) {
			if ( $include_numbers === true ) {
				WP_CLI::line(sprintf('[%s] %s (%s)', $i+1, $zone->name, $zone->id));
			} else {
				WP_CLI::line(sprintf('%s (%s)', $zone->name, $zone->id));
			}
		}
	}

	/**
	 * Store zone without listing available zones.
	 *
	 * @param $zone_id
	 */
	protected function store_zone_direct($zone_id) {
		$zone_object = sb_cf()->get_zone_by_id($zone_id);
		$zone_identification = $zone_object ? $zone_object->name . ' (' . $zone_object->id . ')' : $zone_id;
		if ( ! $zone_object ) {
			WP_CLI::error_multi_line(['Could not find zone with the specified ID in Cloudflare. This might indicate:', '- the API connection is not working', '- that the zone does not exists', '- that we lack access to the zone']);
			WP_CLI::confirm( 'Do you still wish to set the zone?');
		}
		sb_cf()->store_active_zone_id($zone_id);
		WP_CLI::success(sprintf('Successfully selected zone %s', $zone_identification));
	}

	/**
	 * Display Cloudflare config for a given blog.
	 *
	 * @param bool $blog_id
	 *
	 * @return array
	 */
	protected function cf_get_config_for_blog($blog_id = false) {
		$auth_type = sb_cf()->get_authentication_type($blog_id);
		$is_cron_purge = sb_cf()->cron_purge_is_active(true, $blog_id);


		if ( $blog_id ) {
			$arr = ['Blog' => $blog_id];
		} else {
			$arr = [];
		}

		$arr = array_merge($arr, [
			'Status'     => sb_cf()->cf_is_active($blog_id) ? 'Active' : 'Inactive',
			'Zone Id'    => sb_cf()->get_active_zone_id($blog_id),
			'Purge type' => $is_cron_purge ? 'Via cron' : 'Immediate purge',
		]);

		if ($is_cron_purge) {
			$max_items = 50;
			$items_to_purge = sb_cf()->get_items_to_purge($max_items, $blog_id);
			$items_to_purge_count = sb_cf()->count_items_to_purge();
			$arr['Purge queue (cron)'] = $items_to_purge ? sb_format_array_to_csv($items_to_purge) . ( $items_to_purge_count > $max_items ? '...' : '') : null;
		}

		$arr['API authentication type'] = $auth_type;
		switch ($auth_type) {
			case 'apiKey':
			case 'api_key':
				$arr['API key/token'] = sb_cf()->get_credential('api_key', $blog_id);
				$arr['email'] = sb_cf()->get_credential('email', $blog_id);
				break;
			case 'apiToken':
			case 'api_token':
				$arr['API key/token'] = sb_cf()->get_credential('api_token', $blog_id);
				break;
		}
		return $arr;
	}

	/**
	 * Ensure all CF config items have the same column structure.
	 *
	 * @param $array
	 *
	 * @return array
	 */
	protected function cf_ensure_config_array_integrity($array) {

		$most_column_item = [];
		foreach($array as $item) {
			if ( count($item) > count($most_column_item) ) {
				$most_column_item = array_keys($item);
			}
		}

		$new_array = [];
		foreach($array as $item) {
			$new_item = [];
			foreach ($most_column_item as $column) {
				$new_item[$column] = array_key_exists($column, $item) ? $item[$column] : null;
			}
			$new_array[] = $new_item;
		}

		return $new_array;
	}

	/**
	 * Enable/disable Nginx cache headers.
	 *
	 * @param bool $cron_active
	 */
	protected function cf_cron_control(bool $cron_active) {
		$current_state = sb_cf()->cron_purge_is_active();
		if ( $current_state === $cron_active ) {
			WP_CLI::line(sprintf(sb__('Cloudflare cache purge cron is already %s'), sb_boolean_to_state_string($cron_active)));
		} else {
			sb_cf()->cf_toggle_cron_active($cron_active);
			WP_CLI::success(sprintf(sb__('Cloudflare cache purge cron is now %s'), sb_boolean_to_state_string($cron_active)));
		}

		// Add / remove task from schedule
		( Servebolt_CF_Cron_Handle::get_instance() )->update_cron_state();
	}

	/**
	 * Display Nginx status - which post types have cache active.
	 *
	 * @param $assoc_args
	 */
	protected function get_nginx_fpc_status($assoc_args, $display_cache_state = true) {
		if ( is_multisite() && array_key_exists('all', $assoc_args) ) {
			$sites = get_sites();
			$sites_status = [];
			foreach ( $sites as $site ) {
				$status = sb_nginx_fpc()->fpc_is_active($site->blog_id) ? 'Active' : 'Inactive';
				$post_types = sb_nginx_fpc()->get_post_types_to_cache(true, true, $site->blog_id);
				$enabled_post_types_string = $this->nginx_get_active_post_types_string($post_types);
				$sites_status[] = [
					'URL'               => get_site_url($site->blog_id),
					'STATUS'            => $status,
					'ACTIVE_POST_TYPES' => $enabled_post_types_string,
				];
			}
			WP_CLI\Utils\format_items( 'table', $sites_status , array_keys(current($sites_status)));
		} else {
			$status = sb_boolean_to_state_string( sb_nginx_fpc()->fpc_is_active() );
			$post_types = sb_nginx_fpc()->get_post_types_to_cache();
			$enabled_post_types_string = $this->nginx_get_active_post_types_string($post_types);
			if ( $display_cache_state ) {
				WP_CLI::line( sprintf( sb__( 'Servebolt Full Page Cache cache is %s' ), $status ) );
			}
			WP_CLI::line( sprintf( sb__( 'Cache enabled for post type(s): %s' ), $enabled_post_types_string ) );
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
			return sprintf( sb__( 'Default [%s]' ), sb_nginx_fpc()->get_default_post_types_to_cache( 'csv' ) );
		}

		// Cache all post types
		if ( array_key_exists('all', $post_types) ) {
			return sb__( 'All' );
		}
		return sb_format_array_to_csv($post_types);
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
		$exclude_ids         = array_key_exists('exclude', $args) ? $args['exclude'] : false;

		if ( is_multisite() && $affect_all_blogs ) {
			WP_CLI::line( sb__('Applying settings to all blogs') );
			foreach (get_sites() as $site) {
				$this->nginx_toggle_cache_for_blog($cache_active, $site->blog_id);
				if ( $post_types ) $this->nginx_set_post_types($post_types, $site->blog_id);
				if ( $exclude_ids ) $this->nginx_set_exclude_ids($exclude_ids, $site->blog_id);
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
		$all_post_types = $this->nginx_get_all_post_types();
		$all_post_type_keys = array_keys($all_post_types);
		$url = get_site_url($blog_id);
		$post_types = array_filter($post_types, function($post_type) use ($all_post_type_keys) {
			return in_array($post_type, $all_post_type_keys) || $post_type === 'all';
		});
		sb_nginx_fpc()->set_cacheable_post_types($post_types, $blog_id);
		WP_CLI::success(sprintf(sb__('Cache post type(s) set to %s on %s'), sb_format_array_to_csv($post_types), $url));
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
	 */
	protected function nginx_set_exclude_ids($ids_to_exclude) {
		if ( ! is_array($ids_to_exclude) ) {
			$ids_to_exclude = sb_format_comma_string($ids_to_exclude);
		}
		$already_excluded = sb_nginx_fpc()->get_ids_to_exclude_from_cache();

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
		sb_nginx_fpc()->set_ids_to_exclude_from_cache($already_excluded);

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

}
