<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Class Servebolt_CLI_Extras
 * @package Servebolt
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
	protected function getZones() {
		if ( is_null($this->zones) ) {
			$this->zones = sb_cf()->listZones();
		}
		return $this->zones;
	}

	/**
	 * List Cloudflare zones with/without numbering.
	 *
	 * @param bool $includeNumbers
	 */
	protected function listZones($includeNumbers = false) {
		$zones = $this->getZones();
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
				$status = sb_get_blog_option($site->blog_id, 'fpc_switch') === true ? 'activated' : 'deactivated';
				$post_types = sb_get_blog_option($site->blog_id, 'fpc_settings');
				$enabled_post_types_string = $this->nginx_get_active_post_types_string($post_types);
				$sites_status[] = [
					'URL'               => get_site_url($site->blog_id),
					'STATUS'            => $status,
					'ACTIVE_POST_TYPES' => $enabled_post_types_string,
				];

			}
			WP_CLI\Utils\format_items( 'table', $sites_status , array_keys(current($sites_status)));
		} else {
			$status = sb_get_option( 'fpc_switch' ) === true ? 'activated' : 'deactivated';
			$post_types = sb_get_option( 'fpc_settings' );
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
	private function nginx_get_active_post_types_string($post_types)
	{
		$enabled_post_types = [];
		if ( is_array($post_types) ) {
			foreach ( $post_types as $key => $value ) {
				if ( $value ) {
					$enabled_post_types[] = $key;
				}
			}
		}
		$enabled_post_types_string = implode(',', $enabled_post_types);

		// Cache default post types
		if ( empty($enabled_post_types_string) ) {
			return sprintf( sb__( 'Default [%s]' ), sb_nginx_fpc()->defaultCacheablePostTypes( 'csv' ) );
		}

		// Cache all post types
		if (array_key_exists('all', $post_types) ) {
			return sb__( 'All' );
		}

		return $enabled_post_types_string;
	}

	/**
	 *
	 *
	 * @param $state
	 * @param $args
	 * @param $assoc_args
	 */
	protected function nginx_control($state, $args, $assoc_args){
		$switch = '';
		if($state === 'activate') {
			$switch = 'on';
		}elseif($state === 'deactivate'){
			$switch = 'off';
		}
		if(is_multisite() && $state === 'deactivate' && array_key_exists('post_types', $assoc_args) && array_key_exists('all', $assoc_args)){
			$sites = get_sites();
			foreach ($sites as $site) {
				$id = $site->blog_id;
				switch_to_blog($id);
				if(array_key_exists('post_types',$assoc_args)) $this->nginx_set_post_types(explode(',', $assoc_args['post_types']), $state, $id);
				restore_current_blog();
			}
		}
		elseif(is_multisite() && array_key_exists('all', $assoc_args)){
			$sites = get_sites();

			foreach ($sites as $site) {
				$id = $site->blog_id;
				switch_to_blog($id);
				$url = get_site_url($id);
				$status = sb_get_option('fpc_switch');
				if($status !== $switch):
					sb_update_option('fpc_switch', $switch);
					WP_CLI::success(sprintf(sb__('Full Page Cache %1$sd on %2$s'), $state, esc_url($url)));
				elseif($status === $switch):
					WP_CLI::warning(sprintf(sb__('Full Page Cache already %1$sd on %2$s'), $state, esc_url($url)));
				endif;

				if(array_key_exists('post_types',$assoc_args)) $this->nginx_set_post_types(explode(',', $assoc_args['post_types']), $state, $id);
				restore_current_blog();
			}

		}
		elseif($state === 'deactivate' && array_key_exists('post_types', $assoc_args)){
			$this->nginx_set_post_types(explode(',', $assoc_args['post_types']), $state, get_current_blog_id());
		}
		else{
			$status = sb_get_option('fpc_switch');
			if(array_key_exists('post_types',$assoc_args)) $this->nginx_set_post_types(explode(',', $assoc_args['post_types']), $state);
			if($status !== $switch):
				sb_update_option('fpc_switch', $switch);
				WP_CLI::success(sprintf(sb__('Full Page Cache %1$sd'), $state));
			elseif($status === $switch):
				WP_CLI::warning(sprintf(sb__('Full Page Cache already %1$sd'), $state));
			endif;
		}

		if(array_key_exists('exclude', $assoc_args)){
			$this->nginx_set_exclude_ids($assoc_args['exclude']);
		}

	}

	/**
	 * Set post types to cache.
	 *
	 * @param $post_types
	 * @param $state
	 */
	protected function nginx_set_post_types( $post_types , $state){
		$switch = '';
		if($state === 'activate') {
			$switch = 'on';
		}elseif($state === 'deactivate'){
			$switch = 'off';
		}

		$updateOption = sb_get_option('fpc_settings');

		$AllTypes = get_post_types([
			'public' => true
		], 'objects');


		if(in_array('all', $post_types)) {
			$CLIfeedback = sprintf(sb__('Cache deactivated for all post types on %s'), get_home_url());
			$success = true;

			if ( $switch === 'on' ) {

				foreach ( $AllTypes as $type ) {
					$newOption[$type->name] = $switch;
				}
				$updateOption = $newOption;

				$CLIfeedback = sprintf(sb__('Cache activated for all post types on %s'), get_home_url());
				$success = true;

			}

		} else {

			foreach ($post_types as $post_type) if(array_key_exists($post_type, $AllTypes)){
				$updateOption[$post_type] = $switch;
			}

			// remove everything that is off
			foreach ($updateOption as $key => $value){
				if($value === 'off') unset($updateOption[$key]);
			}

			$CLIfeedback = sprintf(sb__('Cache %sd for post type(s) %s on %s'),$state, implode(',', $post_types) , get_home_url());
			$success = true;

		}

		if ($success) {
			WP_CLI::success($CLIfeedback);
		} else {
			WP_CLI::warning($CLIfeedback);
		}

		sb_update_option('fpc_settings', $updateOption);
	}

	/**
	 * Set exclude Ids.
	 *
	 * @param $idsToExcludeString
	 */
	protected function nginx_set_exclude_ids($idsToExcludeString) {

		$idsToExclude = $this->format_id_string($idsToExcludeString);
		$alreadyExcluded = sb_nginx_fpc()->getIdsToExclude();

		if ( empty($idsToExclude) ) {
			WP_CLI::warning(sb__('No ids were specified.'));
			return;
		}

		$alreadyAdded = [];
		$wasExcluded = [];
		$invalidId = [];
		foreach ($idsToExclude as $id) {
			if ( get_post_status( $id ) === false ) {
				$invalidId[] = $id;
			} elseif ( ! in_array($id, $alreadyExcluded)) {
				$wasExcluded[] = $id;
				$alreadyExcluded[] = $id;
			} else {
				$alreadyAdded[] = $id;
			}
		}
		sb_nginx_fpc()->setIdsToExclude($alreadyExcluded);

		if ( ! empty($alreadyAdded) ) {
			WP_CLI::info(sprintf(sb__('The following ids were already excluded: %s'), implode(',', $alreadyAdded)));
		}

		if ( ! empty($invalidId) ) {
			WP_CLI::warning(sprintf(sb__('The following ids were invalid: %s'), implode(',', $alreadyAdded)));
		}

		if ( ! empty($wasExcluded) ) {
			WP_CLI::success(sprintf(sb__('Added %s to the list of excluded ids'), implode(',', $wasExcluded)));
		} else {
			WP_CLI::info(sb__('No action was made.'));
		}

	}

	/**
	 * Format post Ids separated by comma.
	 *
	 * @param $string Comma separated post Ids
	 *
	 * @return array
	 */
	private function format_id_string($string) {
		$idsToExclude = explode(',', $string);
		$idsToExclude = array_map(function ($idToExclude) {
			return $idToExclude;
		}, $idsToExclude);
		return array_filter($idsToExclude, function ($idToExclude) {
			return ! empty($idToExclude);
		});
	}

}
