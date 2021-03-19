<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use Servebolt\Optimizer\DatabaseOptimizer\DatabaseOptimizer;

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
	    $instance = DatabaseOptimizer::getInstance();
        $instance->optimizeDb(true);
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
        $instance = DatabaseOptimizer::getInstance();
		if (!$instance->analyzeTables(true)) {
			WP_CLI::error(__('Could not analyze tables.', 'servebolt-wp'));
		} else {
			WP_CLI::success(__('Analyzed tables.', 'servebolt-wp'));
		}
	}

}
