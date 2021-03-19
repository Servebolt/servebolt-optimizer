<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use function Servebolt\Optimizer\Helpers\arrayGet;
use function Servebolt\Optimizer\Helpers\formatCommaStringToArray;

require_once __DIR__ . '/fpc-extra.class.php';

/**
 * Class Servebolt_CLI_FPC
 *
 * This class contains the FPC-related methods when using the WP CLI.
 */
class Servebolt_CLI_FPC extends Servebolt_CLI_FPC_Extra {

	/**
	 * Activate Servebolt Full Page Cache.
	 *
	 * ## OPTIONS
	 *
	 * [--all]
	 * : Activate on all sites in multisite.
	 *
	 * [--post-types=<post_types>]
	 * : Comma separated list of post types to be activated.
	 *
	 * [--exclude=<ids>]
	 * : Comma separated list of ids to exclude for full page caching. Will be omitted when using the --all flag since post IDs are relative to each site.
	 *
	 * [--status]
	 * : Display status after command is executed.
	 *
	 * ## EXAMPLES
	 *
	 *     # Activate Servebolt Full Page Cache, but only for pages and posts
	 *     wp servebolt fpc activate --post-types=post,page
	 *
	 *     # Activate Servebolt Full Page Cache for all public post types on all sites in multisite-network
	 *     wp servebolt fpc activate --post-types=all --all
	 *
	 */
	public function command_nginx_fpc_enable($args, $assoc_args) {
		$this->nginx_fpc_control(true, $assoc_args);
		if ( in_array('status', $assoc_args) ) $this->get_nginx_fpc_status($assoc_args, false);
	}

	/**
	 * Deactivate Servebolt Full Page Cache.
	 *
	 * ## OPTIONS
	 *
     * [--all]
     * : Activate on all sites in multisite.
     *
	 * [--status]
	 * : Display status after command is executed.
	 *
	 * ## EXAMPLES
	 *
	 *     # Deactivate Servebolt Full Page Cache
	 *     wp servebolt fpc deactivate
     *
     *     # Deactivate Servebolt Full Page Cache for all sites in multisite-network
     *     wp servebolt fpc deactivate --all
	 *
	 */
	public function command_nginx_fpc_disable($args, $assoc_args) {
		$this->nginx_fpc_control(false, $assoc_args);
		if ( in_array('status', $assoc_args) ) $this->get_nginx_fpc_status($assoc_args, false);
	}

	/**
	 * Return status of the Servebolt Full Page Cache.
	 *
	 * ## OPTIONS
	 *
	 * [--all]
	 * : Check status on all sites in multisite-network.
	 *
	 * ## EXAMPLES
	 *
	 *     wp servebolt fpc status
	 *
	 */
	public function command_nginx_fpc_status($args, $assoc_args) {
		if ( $this->affect_all_sites($assoc_args) ) {
			$sites_status = [];
			sb_iterate_sites(function ($site) use (&$sites_status) {
				$sites_status[] = $this->get_nginx_fpc_status($site->blog_id);
			});
		} else {
			$sites_status[] = $this->get_nginx_fpc_status();
		}
		WP_CLI\Utils\format_items( 'table', $sites_status , array_keys(current($sites_status)));
	}

	/**
	 * Get the post types that should be cached with Servebolt Full Page Cache.
	 *
	 * ## OPTIONS
	 *
	 * [--all]
	 * : Get post types on all sites in multisite
	 *
	 *
	 * ## EXAMPLES
	 *
	 *     # Get post types to cache on all sites
	 *     wp servebolt fpc post-types get --all
	 *
	 */
	public function command_nginx_fpc_get_cache_post_types($args, $assoc_args) {
		if ( $this->affect_all_sites($assoc_args) ) {
			$sites_status = [];
			sb_iterate_sites(function ($site) use (&$sites_status) {
				$sites_status[] = $this->nginx_fpc_get_cache_post_types($site->blog_id);
			});
		} else {
			$sites_status[] = $this->nginx_fpc_get_cache_post_types();
		}
		WP_CLI\Utils\format_items( 'table', $sites_status , array_keys(current($sites_status)));
	}

	/**
	 * Set the post types that should be cached with Servebolt Full Page Cache.
	 *
	 * ## OPTIONS
	 *
	 * [--all]
	 * : Set post types on all sites in multisite.
	 *
	 * [--all-post-types]
	 * : Set cache for all post types. When present then --post-types will be omitted.
	 *
	 * [--post-types]
	 * : The post types we would like to cache.
	 *
	 * ## EXAMPLES
	 *
	 *     # Activate cache for given post types on all sites
	 *     wp servebolt fpc post-types set --post-types=page,post --all
	 *
	 *     # Activate cache for all post types on current site
	 *     wp servebolt fpc post-types set --all-post-types
	 *
	 */
	public function command_nginx_fpc_set_cache_post_types($args, $assoc_args) {
		$all_post_types = array_key_exists('all-post-types', $assoc_args);

		if ( $all_post_types ) {
			$post_types = ['all'];
		} else {
			$post_types_string = arrayGet('post-types', $assoc_args, '');
			$post_types = formatCommaStringToArray($post_types_string);
		}

		if ( empty($post_types) ) {
			WP_CLI::error(__('No post types specified', 'servebolt-wp'));
		}

		if ( $this->affect_all_sites($assoc_args) ) {
			sb_iterate_sites(function ( $site ) use ($post_types) {
				$this->nginx_set_post_types($post_types, $site->blog_id);
			});
		} else {
			$this->nginx_set_post_types($post_types);
		}
	}

	/**
	 * Clear post types that should be cached with Servebolt Full Page Cache.
	 *
	 * ## OPTIONS
	 *
	 * [--all]
	 * : Clear post types to cache on all sites in multisite.
	 *
	 * ## EXAMPLES
	 *
	 *     # Clear all post types to cache on all sites
	 *     wp servebolt fpc post-types clear --all
	 *
	 *     # Clear all post types to cache
	 *     wp servebolt fpc post-types clear
	 *
	 */
	public function command_nginx_fpc_clear_cache_post_types($args, $assoc_args) {
		if ( $this->affect_all_sites($assoc_args) ) {
			sb_iterate_sites(function ( $site ) {
				$this->nginx_set_post_types([], $site->blog_id);
			});
		} else {
			$this->nginx_set_post_types([]);
		}
	}

	/**
	 * Get the posts that should be excluded from the cache.
	 *
	 * ## OPTIONS
	 *
	 * [--all]
	 * : Get post to exclude from cachecache on all sites in multisite.
	 *
	 * [--extended]
	 * : Display more details about the excluded posts.
	 *
	 * ## EXAMPLES
	 *
	 *     # Get posts to exclude from cache
	 *     wp servebolt fpc excluded-posts get
	 *
	 *     # Get posts to exclude from cache on all sites in multisite-network.
	 *     wp servebolt fpc excluded-posts get --all
	 *
	 */
	public function command_nginx_fpc_get_excluded_posts($args, $assoc_args) {
		$arr = [];
		$extended = array_key_exists('extended', $assoc_args);
		if ( $this->affect_all_sites($assoc_args) ) {
			sb_iterate_sites(function ( $site ) use (&$arr, $extended) {
				$arr[] = $this->nginx_fpc_get_excluded_posts($site->blog_id, $extended);
			});
		} else {
			$arr[] = $this->nginx_fpc_get_excluded_posts(false, $extended);
		}
		WP_CLI\Utils\format_items('table', $arr, array_keys(current($arr)));
	}

	/**
	 * Set the posts that should be excluded from the cache.
	 *
	 * ## OPTIONS
	 *
	 * <post_ids>
	 * : The posts to exclude from cache.
	 *
	 * ## EXAMPLES
	 *
	 *     # Exclude cache for posts with ID 1, 2 and 3
	 *     wp servebolt fpc excluded-posts set 1,2,3
	 *
	 */
	public function command_nginx_fpc_set_excluded_posts($args, $assoc_args) {
		list($post_ids_raw) = $args;
		$this->nginx_set_exclude_ids($post_ids_raw);
	}

	/**
	 * Clear the posts that was excluded from the cache.
	 *
	 * ## OPTIONS
	 *
	 * [--all]
	 * : Clear post to exclude from cachecache on all sites in multisite.
	 *
	 * ## EXAMPLES
	 *
	 *     # Clear all post types to cache on all sites
	 *     wp servebolt fpc excluded-posts clear --all
	 *
	 *     # Clear all post types to cache
	 *     wp servebolt fpc excluded-posts clear
	 *
	 */
	public function command_nginx_fpc_clear_excluded_posts($args, $assoc_args) {
		if ( $this->affect_all_sites( $assoc_args ) ) {
			sb_iterate_sites(function ( $site ) {
				$this->nginx_set_exclude_ids(false, $site->blog_id);
			});
		} else {
			$this->nginx_set_exclude_ids(false);
		}
	}

}
