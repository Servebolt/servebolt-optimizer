<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Class Servebolt_CLI_Optimizations
 */
class Servebolt_CLI_Optimizations {

	/**
	 * Alias of "wp servebolt db optimize". Add database indexes and convert database tables to modern table types or delete transients.
	 *
	 * ## EXAMPLES
	 *
	 *     wp servebolt db fix
	 */
	public function command_fix() {
		$this->command_optimize_database();
	}

	/**
	 * Add database indexes and convert database tables to modern table types or delete transients.
	 *
	 * ## EXAMPLES
	 *
	 *     wp servebolt db optimize
	 *
	 */
	public function command_optimize_database() {
		sb_optimize_db()->optimize_db(true);
	}

	/**
	 * Analyze tables.
	 *
	 * ## EXAMPLES
	 *
	 *     wp servebolt db analyze
	 *
	 */
	public function command_analyze_tables() {
		if ( ! sb_optimize_db()->analyze_tables(true) ) {
			WP_CLI::error(sb__('Could not analyze tables.'));
		} else {
			WP_CLI::success(sb__('Analyzed tables.'));
		}
	}

}
