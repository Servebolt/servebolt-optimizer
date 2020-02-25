<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Class Servebolt_Checks
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
	public static function instance() {
		if ( self::$instance == null ) {
			self::$instance = new Servebolt_Checks;
		}
		return self::$instance;
	}

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
		global $wpdb;

		$tables = $this->get_table_index_setup();
		$tables_to_have_index = [];

		if ( is_multisite() ) {
			$sites = $this->get_all_tables();
			foreach ( $sites as $key => $value ) {
				foreach ( $tables as $table => $index ) {
					switch_to_blog( $key );

					$a_table = [
						'blog_id' => $key,
						'table'   => $table,
						'name'    => implode( [ $wpdb->prefix, $table ] ),
						'index'   => $index,
					];
					$db_table = $wpdb->{$a_table['table']};
					$indexes  = $wpdb->get_results( "SHOW INDEX FROM {$db_table}" );

					foreach ( $indexes as $index ) {
						if ( $index->Column_name == $a_table['index'] ) {
							$a_table['has_index'] = true;
						} else {
							$a_table['has_index'] = false;
						}
					}
					$tables_to_have_index[] = $a_table;
					restore_current_blog();
				}
			}
		} else {
			foreach ( $tables as $table => $index ) {
				$a_table = [
					'name'  => $table,
					'table' => $table,
					'index' => $index,
				];

				$db_table = $wpdb->$table;
				$indexes  = $wpdb->get_results( "SHOW INDEX FROM {$db_table}" );

				foreach ( $indexes as $index ) {
					if ( $index->Column_name == $a_table['index'] ) {
						$a_table['has_index'] = true;
					} else {
						$a_table['has_index'] = false;
					}
				}
				$tables_to_have_index[] = $a_table;
			}
		}

		return $tables_to_have_index;
	}

	/**
	 * Get all tables in database.
	 *
	 * @return array
	 */
	private function get_all_tables(){
		global $wpdb;
		if ( is_multisite() ) {
			$sites = get_sites();
			$tables = [];
			foreach ($sites as $site){
				$id = $site->blog_id;
				switch_to_blog($id);
				$siteTables = $wpdb->tables;
				$tables[$id] = array_flip($siteTables);
				restore_current_blog();
			}
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
		return $wpdb->get_results("SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE engine = 'myisam' AND TABLE_NAME LIKE '{$wpdb->prefix}%'");
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
