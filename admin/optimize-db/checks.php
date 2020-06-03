<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Class Servebolt_Checks
 *
 * This class will:
 * - Check the option and postmeta table for missing indexes, and will add them if not present
 * - Check for tables using the MyISAM DB-engine and convert them to InnoDB.
 */
class Servebolt_Checks {

	/**
	 * @var null Singleton instance.
	 */
	private static $instance = null;

	/**
	 * Singleton instantiation.
	 *
	 * @return Servebolt_Checks|null
	 */
	public static function get_instance() {
		if ( self::$instance == null ) {
			self::$instance = new Servebolt_Checks;
		}
		return self::$instance;
	}

	/**
	 * Servebolt_Checks constructor.
	 */
	private function __construct() {}

	/**
	 * Return array of index setup for tables.
	 *
	 * @return array
	 */
	public function get_table_index_setup() {
		return [
			'options'  => 'autoload',
			'postmeta' => 'meta_value',
		];
	}

	/**
	 * Check if a table is valid for InnoDB conversion.
	 *
	 * @param $table_name
	 *
	 * @return bool
	 */
	public function table_valid_for_innodb_conversion($table_name) {
		$myisam_tables = $this->get_myisam_tables();
		if ( is_array($myisam_tables) ) {
			foreach ( $myisam_tables as $myisam_table ) {
				if ( $myisam_table->TABLE_NAME == $table_name ) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Get column name to index for specific table.
	 *
	 * @param $table_name
	 *
	 * @return bool|mixed
	 */
	public function get_index_column_from_table($table_name) {
		$tables = $this->get_table_index_setup();
		if ( array_key_exists($table_name, $tables) ) {
			return $tables[$table_name];
		}
		return false;
	}

	/**
	 * Get tables that should add index to.
	 *
	 * @return array
	 */
	public function tables_to_have_index() {
		$index_table_types = $this->get_table_index_setup();
		$tables_to_have_index = [];
		if ( is_multisite() ) {
			$tables = $this->get_all_tables();
			foreach ( $tables as $blog_id => $value ) {
				foreach ( $index_table_types as $table => $index ) {
					$tables_to_have_index[] = $this->check_if_table_has_index($table, $index, $blog_id);
				}
			}
		} else {
			foreach ( $index_table_types as $table => $index ) {
				$tables_to_have_index[] = $this->check_if_table_has_index($table, $index);
			}
		}
		return $tables_to_have_index;
	}

	/**
	 * Check if table has an index.
	 *
	 * @param $table_name
	 * @param $index
	 * @param bool $blog_id
	 *
	 * @return array
	 */
	private function check_if_table_has_index($table_name, $index, $blog_id = false) {
		global $wpdb;
		if ( $blog_id ) {
			switch_to_blog($blog_id);
			$table = ['blog_id' => $blog_id];
		} else {
			$table = [];
		}
		$db_table = $wpdb->{$table_name};

		$table['name']  = $db_table;
		$table['table'] = $table_name;
		$table['index'] = $index;
		$indexes  = $wpdb->get_results( "SHOW INDEX FROM {$db_table}" );
		foreach ( $indexes as $index ) {
			$table['has_index'] = ( $index->Column_name == $table['index'] );
		}
		if ( $blog_id ) restore_current_blog();
		return $table;
	}

	/**
	 * Get all tables in database.
	 *
	 * @return array
	 */
	private function get_all_tables(){
		global $wpdb;
		if ( is_multisite() ) {
			$tables = [];
			sb_iterate_sites(function($site) use (&$tables, $wpdb) {
				switch_to_blog($site->blog_id);
				$tables[$site->blog_id] = array_flip($wpdb->tables);
				restore_current_blog();
			});
		} else {
			$tables = $wpdb->tables;
		}
		return $tables;
	}

	/**
	 * Get all tables using MyISAM.
	 *
	 * @return array|object|null
	 */
	public function get_myisam_tables() {
		global $wpdb;
		return $wpdb->get_results("SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE LOWER(engine) = 'myisam' AND TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME LIKE '{$wpdb->prefix}%'");
	}

	/**
	 * Check if WP cron is disabled.
	 *
	 * @return bool
	 */
	function wp_cron_disabled() {
		return defined('DISABLE_WP_CRON') && DISABLE_WP_CRON === true;
	}

}
