<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Class Servebolt_CLI_General
 */
class Servebolt_CLI_General {

	/**
	 * Delete all settings related to this plugin.
	 *
	 * ## EXAMPLES
	 *
	 *     wp servebolt delete-all-settings
	 */
	public function command_delete_all_settings() {
		sb_delete_all_settings();
		WP_CLI::confirm(sb__('Do you really want to delete all settings?'));
		WP_CLI::success(sb__('All settings deleted!'));
	}

}
