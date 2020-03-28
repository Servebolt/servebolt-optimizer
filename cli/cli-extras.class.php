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
	 * Check if we should affect all sites in multisite-network.
	 *
	 * @param $assoc_args
	 *
	 * @return bool
	 */
	protected function affect_all_sites($assoc_args) {
		return is_multisite() && array_key_exists('all', $assoc_args);
	}

}
