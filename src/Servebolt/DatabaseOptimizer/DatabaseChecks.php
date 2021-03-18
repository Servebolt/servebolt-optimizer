<?php

namespace Servebolt\Optimizer\DatabaseOptimizer;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Traits\Singleton;

/**
 * Class DatabaseChecks
 *
 * This class will:
 * - Check the option and postmeta table for missing indexes, and will add them if not present
 * - Check for tables using the MyISAM DB-engine and convert them to InnoDB.
 */
class DatabaseChecks
{
    use Singleton;

	/**
	 * Return array of index setup for tables.
	 *
	 * @return array
	 */
	public function getTableIndexSetup(): array
    {
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
	public function tableValidForInnodbConversion($table_name) {
		$myisam_tables = $this->getMyisamTables();
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
	public function getIndexColumnFromTable($table_name) {
		$tables = $this->getTableIndexSetup();
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
	public function tablesToHaveIndexed()
    {
		$indexTableTypes = $this->getTableIndexSetup();
		$tablesToHaveIndex = [];
		if ( is_multisite() ) {
			$tables = $this->getAllTables();
			foreach ( $tables as $blog_id => $value ) {
				foreach ($indexTableTypes as $table => $index) {
                    $tablesToHaveIndex[] = $this->checkIfTableHasIndex($table, $index, $blog_id);
				}
			}
		} else {
			foreach ($indexTableTypes as $table => $index) {
                $tablesToHaveIndex[] = $this->checkIfTableHasIndex($table, $index);
			}
		}
		return $tablesToHaveIndex;
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
	private function checkIfTableHasIndex($table_name, $index, $blog_id = false)
    {
		global $wpdb;
		if ($blog_id) {
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
		foreach ($indexes as $index) {
			$table['has_index'] = ( $index->Column_name == $table['index'] );
		}
		if ($blog_id) {
            restore_current_blog();
        }
		return $table;
	}

	/**
	 * Get all tables in database.
	 *
	 * @return array
	 */
	private function getAllTables()
    {
		global $wpdb;
		if (is_multisite()) {
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
	public function getMyisamTables()
    {
		global $wpdb;
		return $wpdb->get_results("SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE LOWER(engine) = 'myisam' AND TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME LIKE '{$wpdb->prefix}%'");
	}

	/**
	 * Check if WP cron is disabled.
	 *
	 * @return bool
	 */
	function wpCronDisabled(): bool
    {
		return defined('DISABLE_WP_CRON') && DISABLE_WP_CRON === true;
	}

}
