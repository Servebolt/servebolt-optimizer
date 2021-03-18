<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Class Servebolt_CLI_General
 */
class Servebolt_CLI_General extends Servebolt_CLI_Extras {

	/**
	 * Delete all settings related to this plugin.
	 *
	 * ## OPTIONS
	 *
	 * [--all]
	 * : Delete all settings on all sites in multisite-network.
	 *
	 * ## EXAMPLES
	 *
	 *     wp servebolt delete-all-settings
	 */
	public function command_delete_all_settings($args, $assoc_args) {
		$affect_all_sites = $this->affect_all_sites( $assoc_args );
		if ( $affect_all_sites ) {
			WP_CLI::confirm(sb__('Do you really want to delete all settings? This will affect all sites in multisite-network.'));
		} else {
			WP_CLI::confirm(sb__('Do you really want to delete all settings?'));
		}
		sbDeleteAllSettings($affect_all_sites);
		WP_CLI::success(sb__('All settings deleted!'));
	}

}
