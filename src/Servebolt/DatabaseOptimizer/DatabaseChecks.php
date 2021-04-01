<?php

namespace Servebolt\Optimizer\DatabaseOptimizer;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Traits\Singleton;
use function Servebolt\Optimizer\Helpers\iterateSites;

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
	 * @param string $tableName
	 *
	 * @return bool
	 */
	public function tableValidForInnodbConversion(string $tableName)
    {
		$myisamTables = $this->getMyisamTables();
		if (is_array($myisamTables)) {
			foreach ($myisamTables as $myisamTable) {
				if ($myisamTable->TABLE_NAME == $tableName) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Get column name to index for specific table.
	 *
	 * @param string $tableName
	 *
	 * @return bool|mixed
	 */
	public function getIndexColumnFromTable(string $tableName)
    {
		$tables = $this->getTableIndexSetup();
		if ( array_key_exists($tableName, $tables) ) {
			return $tables[$tableName];
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
			foreach ($tables as $blogId => $value) {
				foreach ($indexTableTypes as $table => $index) {
                    $tablesToHaveIndex[] = $this->checkIfTableHasIndex($table, $index, $blogId);
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
	 * @param $tableName
	 * @param $index
	 * @param null|int $blogId
	 *
	 * @return array
	 */
	private function checkIfTableHasIndex($tableName, $index, ?int $blogId = null)
    {
		global $wpdb;
		if ($blogId) {
			switch_to_blog($blogId);
			$table = ['blog_id' => $blogId];
		} else {
			$table = [];
		}
		$dbTable = $wpdb->{$tableName};

		$table['name']  = $dbTable;
		$table['table'] = $tableName;
		$table['index'] = $index;
		$indexes  = $wpdb->get_results( "SHOW INDEX FROM {$dbTable}" );
		foreach ($indexes as $index) {
			$table['has_index'] = ( $index->Column_name == $table['index'] );
		}
		if ($blogId) {
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
            iterateSites(function($site) use (&$tables, $wpdb) {
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
