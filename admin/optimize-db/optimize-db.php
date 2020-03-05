<?php
if( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Class Servebolt_Optimize_DB
 */
class Servebolt_Optimize_DB {

	/**
	 * @var bool Whether we will do a dry run or not.
	 */
	private $dry_run = false;

	/**
	 * @var null Whether we added one or more post meta value indexes during the optimization.
	 */
	private $meta_value_index_addition = null;

	/**
	 * @var null Whether we added one or more option autoload indexes during the optimization.
	 */
	private $autoload_index_addition = null;

	/**
	 * @var null Whether we converted one or more tables to InnoDB.
	 */
	private $InnoDB_conversion = null;

	/**
	 * @var array The tasks done while running optimization.
	 */
	private $tasks = [];

	/**
	 * @var null Sites in a multisite setup.
	 */
	private $sites = null;

	/**
	 * @var bool Whether we run this via CLI or not.
	 */
	private $cli = false;

	/**
	 * @var null Singleton instance.
	 */
	private static $instance = null;

	/**
	 * Singleton instantiation.
	 *
	 * @return Servebolt_Optimize_DB|null
	 */
	public static function getInstance() {
		if ( self::$instance == null ) {
			self::$instance = new Servebolt_Optimize_DB;
		}
		return self::$instance;
	}

	/**
	 * Servebolt_Optimize_DB constructor.
	 */
	private function __construct() {
		$this->add_cron_handling();
	}

	/**
	 * Run database optimization.
	 *
	 * @param bool $cli
	 *
	 * @return bool
	 */
	public function optimize_db($cli = false) {
		$this->cli = $cli;
		$this->reset_result_variables();
		$this->optimize_post_meta_tables();
		$this->optimize_options_tables();
		$this->convert_tables_to_innodb();
		return $this->handle_result();
	}

	/**
	 * Handle optimization result.
	 *
	 * @return bool|string
	 */
	private function handle_result() {
		if ( ! $this->did_do_changes() ) {
			$result_string = sb__('No changes needed, database looks healthy, and everything is good!');
			if ( $this->cli ) {
				$this->out($result_string);
				return true;
			} else {
				return [
					'result'  => true,
					'message' => $result_string,
				];
			}
		}

		if ( $this->cli ) {
			$this->out('Changes were made, database looks healthy, and everything is good!');
			return true;
		} else {
			return [
				'result'  => true,
				'message' => sb__('Nice, we got to do some changes to the database!'),
				'tasks'   => $this->tasks,
			];
		}
	}

	/**
	 * Handle table analyze cron.
	 */
	private function add_cron_handling() {
		add_action('servebolt_cron_analyze_tables', [$this, 'analyze_tables']);
	}

	/**
	 * Generate a name for the site.
	 *
	 * @param $site
	 *
	 * @return string
	 */
	private function blog_identification($site) {
		return trim($site->domain . $site->path, '/');
	}

	/**
	 * Remove table optimization measures.
	 */
	public function deoptimize_indexed_tables() {
		foreach ( $this->get_sites() as $site ) {
			switch_to_blog( $site->blog_id );
			$this->remove_post_meta_index();
			$this->remove_options_autoload_index();
			restore_current_blog();
		}
	}

	/**
	 * Attempt to convert all non-InnoDB tables to InnoDB.
	 */
	public function convert_tables_to_non_innodb() {
		$tables = $this->get_innodb_tables();
		if( is_array($tables) && ! empty($tables) ) {
			foreach ( $tables as $table ) {
				if ( ! isset($table->table_name) ) continue;
				$this->convert_table_to_myisam($table->table_name);
			}
		}
	}

	/**
	 * Optimize post meta table by adding an index to the value-column.
	 */
	private function optimize_post_meta_tables() {
		$post_meta_tables_with_post_meta_value_index = $this->tables_with_index_on_column('postmeta', 'meta_value');
		if ( is_multisite() ) {
			foreach ( $this->get_sites() as $site ) {
				switch_to_blog( $site->blog_id );
				if ( ! in_array($this->wpdb()->postmeta, $post_meta_tables_with_post_meta_value_index ) ) {
					if ( $this->dry_run || $this->add_post_meta_index() ) {
						$this->out(sprintf( 'Added index to table "%s" on site %s (site ID %s)', $this->wpdb()->postmeta, $this->blog_identification($site), $site->blog_id));
						$this->meta_value_index_addition['success'][] = $site->blog_id;
					} else {
						$this->out(sprintf( 'Could not add index to table "%s" on site %s (site ID %s)', $this->wpdb()->postmeta, $this->blog_identification($site), $site->blog_id));
						$this->meta_value_index_addition['fail'][] = $site->blog_id;
					}
				}
				restore_current_blog();
			}

		} else {
			if ( ! in_array($this->wpdb()->postmeta, $post_meta_tables_with_post_meta_value_index ) ) {
				if ( $this->dry_run || $this->add_post_meta_index() ) {
					$this->out(sprintf('Added index to table "%s"', $this->wpdb()->postmeta));
					$this->meta_value_index_addition = true;
				} else {
					$this->out(sprintf('Could not add index to table "%s"', $this->wpdb()->postmeta));
					$this->meta_value_index_addition = false;
				}
			}
		}
	}

	/**
	 * Optimize the options table by adding an index to the autoload-column.
	 */
	private function optimize_options_tables() {
		$options_tables_with_autoload_index = $this->tables_with_index_on_column('options', 'autoload');
		if ( is_multisite() ) {
			foreach ( $this->get_sites() as $site ) {
				switch_to_blog( $site->blog_id );
				if ( ! in_array($this->wpdb()->options, $options_tables_with_autoload_index ) ) {
					if ( $this->dry_run || $this->add_options_autoload_index() ) {
						$this->out(sprintf( 'Added index to table "%s" on site %s (site ID %s)', $this->wpdb()->options, $this->blog_identification($site), $site->blog_id ));
						$this->autoload_index_addition['success'][] = $site->blog_id;
					} else {
						$this->out(sprintf( 'Could not add index to table "%" on site %s (site ID %s)', $this->wpdb()->options, $this->blog_identification($site), $site->blog_id ));
						$this->autoload_index_addition['fail'][] = $site->blog_id;
					}
				}
				restore_current_blog();
			}
		} else {
			if ( ! in_array($this->wpdb()->options, $options_tables_with_autoload_index ) ) {
				if ( $this->dry_run || $this->add_options_autoload_index() ) {
					$this->out('Added index to table "options-table');
					$this->autoload_index_addition = true;
				} else {
					$this->out('Could not add index to options-table');
					$this->autoload_index_addition = false;
				}

			}
		}
	}

	/**
	 * Get tables that has an index on a given column.
	 *
	 * @param $table_name
	 * @param $column_name
	 *
	 * @return array
	 */
	private function tables_with_index_on_column($table_name, $column_name) {
		$tables = [];
		if ( is_multisite() ) {
			foreach ( $this->get_sites() as $site ) {
				switch_to_blog($site->blog_id);
				$tables[] = $this->wpdb()->get_results("SHOW INDEX FROM {$this->wpdb()->prefix}{$table_name}");
				restore_current_blog();
			}
		} else {
			$tables[] = $this->wpdb()->get_results("SHOW INDEX FROM {$this->wpdb()->prefix}{$table_name}");
		}

		$tables_with_index_on_column = [];
		foreach ( $tables as $table ) {
			foreach ( $table as $index ) {
				if ( ! isset($index->Column_name) ) continue;
				if ( $index->Column_name == $column_name ) {
					$tables_with_index_on_column[] = $index->Table;
				}
			}
		}

		return $tables_with_index_on_column;
	}

	/**
	 * Add options autoload index to the options table.
	 *
	 * @param bool $blog_id
	 *
	 * @return bool
	 */
	public function add_options_autoload_index($blog_id = false) {
		if ( $blog_id ) {
			switch_to_blog($blog_id);
		}
		$this->wpdb()->query("ALTER TABLE {$this->wpdb()->options} ADD INDEX(autoload)");
		$result = $this->table_has_index($this->wpdb()->options, 'autoload');
		if ( $blog_id ) {
			restore_current_blog();
		}
		return $result;
	}

	/**
	 * Add options autoload index to the options table.
	 *
	 * @param bool $blog_id
	 *
	 * @return bool
	 */
	public function remove_options_autoload_index($blog_id = false) {
		if ( $blog_id ) {
			switch_to_blog($blog_id);
		}
		$this->wpdb()->query("ALTER TABLE {$this->wpdb()->options} DROP INDEX `autoload`");
		$result = ! $this->table_has_index($this->wpdb()->options, 'autoload');
		if ( $blog_id ) {
			restore_current_blog();
		}
		return $result;
	}

	/**
	 * Add post meta value index to the post meta table.
	 *
	 * @param bool $blog_id
	 *
	 * @return bool
	 */
	public function add_post_meta_index($blog_id = false) {
		if ( $blog_id ) {
			switch_to_blog($blog_id);
		}
		$this->wpdb()->query("ALTER TABLE {$this->wpdb()->postmeta} ADD INDEX `sbpmv` (`meta_value`(10))");
		$result = $this->table_has_index($this->wpdb()->postmeta, 'sbpmv');
		if ( $blog_id ) {
			restore_current_blog();
		}
		return $result;
	}

	/**
	 * Remove post meta value index to the post meta table.
	 *
	 * @param bool $blog_id
	 *
	 * @return bool
	 */
	public function remove_post_meta_index($blog_id = false) {
		if ( $blog_id ) {
			switch_to_blog($blog_id);
		}
		$this->wpdb()->query("ALTER TABLE {$this->wpdb()->postmeta} DROP INDEX `sbpmv`");
		$result = ! $this->table_has_index($this->wpdb()->postmeta, 'sbpmv');
		if ( $blog_id ) {
			restore_current_blog();
		}
		return $result;
	}

	/**
	 * Get table name by blog Id.
	 *
	 * @param $blog_id
	 * @param $table
	 *
	 * @return mixed
	 */
	public function get_table_name_by_blog_id($blog_id, $table) {
		switch_to_blog($blog_id);
		$table_name = $this->wpdb()->{$table};
		restore_current_blog();
		return $table_name;
	}

	/**
	 * Check if a table has an index.
	 *
	 * @param $table_name
	 * @param $index_name
	 *
	 * @return bool
	 */
	public function table_has_index($table_name, $index_name) {
		$indexes = $this->wpdb()->get_results("SHOW INDEX FROM {$table_name}");
		if ( is_array($indexes) ) {
			foreach ( $indexes as $index ) {
				if ( ! isset($index->Key_name) ) continue;
				if ( $index->Key_name == $index_name ) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Check if a table has an index.
	 *
	 * @param $table_name
	 * @param $column_name
	 *
	 * @return bool
	 */
	public function table_has_index_on_column($table_name, $column_name) {
		$indexes = $this->wpdb()->get_results("SHOW INDEX FROM {$table_name}");
		if ( is_array($indexes) ) {
			foreach ( $indexes as $index ) {
				if ( ! isset($index->Column_name) ) continue;
				if ( $index->Column_name == $column_name ) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Get WPDB instance.
	 *
	 * @return mixed
	 */
	private function wpdb() {
		return $GLOBALS['wpdb'];
	}

	/**
	 * Convert a table to InnoDB.
	 *
	 * @param $table_name
	 *
	 * @return bool
	 */
	public function convert_table_to_innodb($table_name) {
		$this->wpdb()->query("ALTER TABLE {$table_name} ENGINE = InnoDB");
		return $this->table_is_innodb($table_name);
	}

	/**
	 * Convert a table for MyISAM.
	 *
	 * @param $table_name
	 *
	 * @return bool
	 */
	public function convert_table_to_myisam($table_name) {
		$this->wpdb()->query("ALTER TABLE {$table_name} ENGINE = MyISAM");
		return $this->table_has_engine($table_name, 'myisam');
	}

	/**
	 * Check if a table is using a given engine.
	 *
	 * @param $table_name
	 *
	 * @return bool
	 */
	public function table_has_engine($table_name, $engine) {
		$sql = $this->wpdb()->prepare("SELECT count(*) AS count FROM INFORMATION_SCHEMA.TABLES WHERE engine = %s and TABLE_NAME = %s", $engine, $table_name);
		$check = $this->wpdb()->get_var($sql);
		return $check == '1';
	}

	/**
	 * Check if a table is using the InnoDB engine.
	 *
	 * @param $table_name
	 *
	 * @return bool
	 */
	public function table_is_innodb($table_name) {
		return $this->table_has_engine($table_name, 'innodb');
	}

	/**
	 * Get all tables that are not using the InnoDB engine.
	 *
	 * @return mixed
	 */
	private function get_non_innodb_tables() {
		return $this->wpdb()->get_results("SELECT *, table_name FROM INFORMATION_SCHEMA.TABLES WHERE engine != 'innodb' and TABLE_NAME like '{$this->wpdb()->prefix}%'");
	}

	/**
	 * Get all tables that are not using the InnoDB engine.
	 *
	 * @return mixed
	 */
	private function get_innodb_tables() {
		return $this->wpdb()->get_results("SELECT *, table_name FROM INFORMATION_SCHEMA.TABLES WHERE engine = 'innodb' and TABLE_NAME like '{$this->wpdb()->prefix}%'");
	}

	/**
	 * Attempt to convert all non-InnoDB tables to InnoDB.
	 */
	private function convert_tables_to_innodb() {
		$tables = $this->get_non_innodb_tables();
		if( is_array($tables) && ! empty($tables) ) {
			foreach ( $tables as $table ) {
				if ( ! isset($table->table_name) ) continue;
				if ( $this->dry_run || $this->convert_table_to_innodb($table->table_name) ) {
					$this->out(sprintf('Converted table "%s" to InnoDB', $table->table_name));
					$this->InnoDB_conversion['success'] = $table->table_name;
				} else {
					$this->out(sprintf('Could not convert table "%s" to InnoDB', $table->table_name));
					$this->InnoDB_conversion['fail'] = $table->table_name;
				}
			}
		}
	}

	/**
	 * Analyze tables.
	 *
	 * @param bool $cli
	 *
	 * @return bool
	 */
	function analyze_tables($cli = false) {
		$this->analyze_tables_query();
		if ( is_multisite() ) {
			$this->wpdb()->query( "ANALYZE TABLE {$this->wpdb()->sitemeta}" );
			$site_blog_ids = $this->wpdb()->get_col($this->wpdb()->prepare("SELECT blog_id FROM {$this->wpdb()->blogs} where blog_id > 1"));
			foreach ($site_blog_ids AS $blog_id) {
				switch_to_blog( $blog_id );
				$this->analyze_tables_query();
			}
		}
		if ( $cli ) return true;
	}

	/**
	 * Execute table analysis query.
	 *
	 * @param bool $wpdb_instance
	 */
	private function analyze_tables_query($wpdb_instance = false) {
		if ( ! $wpdb_instance ) {
			$wpdb_instance = $this->wpdb();
		}
		$wpdb_instance->query( "ANALYZE TABLE {$wpdb_instance->posts}" );
		$wpdb_instance->query( "ANALYZE TABLE {$wpdb_instance->postmeta}" );
		$wpdb_instance->query( "ANALYZE TABLE {$wpdb_instance->options}" );
	}

	/**
	 * Check if the optimization resulted in changes being done.
	 *
	 * @return bool
	 */
	private function did_do_changes() {

		if ( is_array($this->meta_value_index_addition) && ( count($this->meta_value_index_addition['success']) > 0 || count($this->meta_value_index_addition['fail']) > 0 ) ) {
			return true;
		} elseif ( is_bool( $this->meta_value_index_addition ) ) {
			return true;
		}

		if ( is_array($this->autoload_index_addition) && ( count($this->autoload_index_addition['success']) > 0 || count($this->autoload_index_addition['fail']) > 0 ) ) {
			return true;
		} elseif ( is_bool( $this->autoload_index_addition ) ) {
			return true;
		}

		if ( is_array($this->InnoDB_conversion) && ( count($this->InnoDB_conversion['success']) > 0 || count($this->InnoDB_conversion['fail']) > 0 ) ) {
			return true;
		} elseif ( is_bool( $this->InnoDB_conversion ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Get all sites in a multisite setup.
	 *
	 * @return array
	 */
	private function get_sites() {
		if ( is_null($this->sites) ) {
			$this->sites = get_sites() ?: [];
		}
		return $this->sites;
	}

	/**
	 * Handle output.
	 *
	 * @param $string
	 * @param bool $include_cr
	 */
	private function out($string, $include_cr = true) {
		if ( $this->cli ) {
			echo $string . ( $include_cr ? PHP_EOL : null );
		} else {
			$this->tasks[] = $string;
		}
	}

	/**
	 * Set result variables to defaults.
	 */
	private function reset_result_variables() {
		foreach( ['meta_value_index_addition', 'autoload_index_addition', 'InnoDB_conversion'] as $key ) {
			if ( is_multisite() ) {
				$this->{$key} = [
					'success' => [],
					'fail'    => [],
				];
			} else {
				$this->{$key} = null;
			}
		}
	}

}
Servebolt_Optimize_DB::getInstance();
