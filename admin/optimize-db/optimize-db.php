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
	private $innodb_conversion = null;

	/**
	 * @var array The tasks done while running optimization.
	 */
	private $tasks = [];

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
	public static function get_instance() {
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
		if ( $this->cli ) {
			WP_CLI::line(sb__('Starting optimization...'));
		}
		$this->reset_result_variables();
		$this->optimize_post_meta_tables();
		$this->optimize_options_tables();
		$this->convert_tables_to_innodb();
		return $this->handle_result();
	}

	/**
	 * Set result variables to defaults.
	 */
	private function reset_result_variables() {
		$boilerplate_array = [
			'success' => [],
			'fail'    => [],
			'count'   => 0,
		];
		if ( is_multisite() ) {
			$this->meta_value_index_addition = $boilerplate_array;
			$this->autoload_index_addition = $boilerplate_array;
			$this->innodb_conversion = $boilerplate_array;
		} else {
			$this->meta_value_index_addition = null;
			$this->autoload_index_addition = null;
			$this->innodb_conversion = $boilerplate_array;
		}
	}

	/**
	 * @param $result
	 * @param $message
	 * @param bool $tasks
	 *
	 * @return array
	 */
	private function return_result($result, $message, $tasks = false) {
		if ( $this->cli ) {
			$this->out($message, ( $result ? 'success' : 'error' ));
			return $result;
		} else {
			return compact('result', 'message', 'tasks');
		}
	}

	/**
	 * Handle optimization result.
	 *
	 * @return array
	 */
	private function handle_result() {
		$result = $this->parse_result();

		if ( is_null($result) ) {
			// No changes made
			return $this->return_result( true, sb__('No changes needed, database looks healthy, and everything is good!') );
		} elseif ( $result === true ) {
			// Changes made and they were done successfully
			return $this->return_result( true, sb__('Nice, we got to do some changes to the database and it seems that we we\'re successful!'), $this->tasks );
		} else {

			// We did some changes, and got one or more errors
			if ( $this->cli ) {
				WP_CLI::line(str_repeat('-', 20) . PHP_EOL . __('Summary:'));
				foreach($result as $key => $value) {
					switch ($value['type']) {
						case 'success':
							WP_CLI::success($value['message']);
							break;
						case 'error':
							WP_CLI::error($value['message'], false);
							break;
						case 'table':
							WP_CLI\Utils\format_items( 'table', $value['table'] , array_keys(current($value['table'])));
							break;
					}
				}
			} else {
				return $this->return_result( true, sb__( '' ), $this->tasks );
			}

		}
	}

	/**
	 * Check how the optimization resulted.
	 *
	 * @return bool
	 */
	private function parse_result() {

		$result = [];
		$meta_value_index_addition = false;
		$autoload_index_addition = false;
		$innodb_conversion = false;

		// Validate creation of meta_value-column index
		if ( is_bool( $this->meta_value_index_addition ) || is_null($this->meta_value_index_addition) ) {
			if ( is_null($this->meta_value_index_addition) ) {
				$result['meta_value_index_addition'] = [
					'type'    => 'success',
					'message' => sb__('Post meta-table already have an index on the meta_value-column.'),
				];
				$meta_value_index_addition = null;
			} elseif ( $this->meta_value_index_addition === false ) {
				$result['meta_value_index_addition'] = [
					'type'    => 'error',
					'message' => sb__('Could not add meta_value-column index on post meta-table.'),
				];
				$meta_value_index_addition = false;
			} else {
				$result['meta_value_index_addition'] = [
					'type'    => 'success',
					'message' => sb__('Added meta_value-column index to post meta-table.'),
				];
				$meta_value_index_addition = true;
			}
		} elseif ( is_array($this->meta_value_index_addition) ) {
			if ( $this->meta_value_index_addition['count'] === 0 ) {
				$result['meta_value_index_addition'] = [
					'type'    => 'success',
					'message' => sb__('All post meta-tables already has an index on the meta_value-column.'),
				];
				$meta_value_index_addition = null;
			} elseif ( count($this->meta_value_index_addition['fail']) > 0 ) {
				if ( count($this->meta_value_index_addition['fail']) === $this->meta_value_index_addition['count'] ) {
					$result['meta_value_index_addition'] = [
						'type'    => 'error',
						'message' => sb__('Failed to add meta_value-column index on all post meta-tables.'),
					];
				} else {
					$failed_blog_urls = array_map(function($blog_id) {
						return get_site_url($blog_id);
					}, $this->meta_value_index_addition['fail']);
					$result['meta_value_index_addition'] = [
						'type'  => 'table',
						'table' => [ sb__('Failed to add meta_value-column index to post meta-tables on sites:') => $failed_blog_urls ]
					];
				}
				$meta_value_index_addition = false;
			} else {
				$result['meta_value_index_addition'] = [
					'type'    => 'success',
					'message' => sb__('Added meta_value-column index to all post meta-tables.'),
				];
				$meta_value_index_addition = true;
			}
		}

		// Validate creation of autoload-column index
		if ( is_bool( $this->autoload_index_addition ) || is_null($this->autoload_index_addition) ) {
			if ( is_null($this->autoload_index_addition) ) {
				$result['autoload_index_addition'] = [
					'type'    => 'success',
					'message' => sb__('Options-table already have an index on the autoload-column.'),
				];
				$autoload_index_addition = null;
			} elseif ( $this->autoload_index_addition === false ) {
				$result['autoload_index_addition'] = [
					'type'    => 'error',
					'message' => sb__('Could not add autoload-column index on options-table.'),
				];
				$autoload_index_addition = false;
			} else {
				$result['autoload_index_addition'] = [
					'type'    => 'success',
					'message' => sb__('Added autoload-column index to options-table.'),
				];
				$autoload_index_addition = true;
			}
		} elseif ( is_array($this->autoload_index_addition) ) {
			if ( $this->autoload_index_addition['count'] === 0 ) {
				$result['autoload_index_addition'] = [
					'type' => 'success',
					'message' => sb__('All options-tables already has an index on the autoload-column.'),
				];
				$autoload_index_addition = null;
			} elseif ( count($this->autoload_index_addition['fail']) > 0 ) {
				if ( count($this->autoload_index_addition['fail']) === $this->autoload_index_addition['count'] ) {
					$result['autoload_index_addition'] = [
						'type'    => 'error',
						'message' => sb__('Failed to add autoload-column index on all options-tables'),
					];
				} else {
					$failed_blog_urls = array_map(function($blog_id) {
						return [ sb__('Failed to add autoload-column index to options-tables on sites:') => get_site_url($blog_id) ];
					}, $this->autoload_index_addition['fail']);
					$result['autoload_index_addition'] = [
						'type'  => 'table',
						'table' => $failed_blog_urls,
					];
				}
				$autoload_index_addition = false;
			} else {
				$result['autoload_index_addition'] = [
					'type'    => 'success',
					'message' => sb__('Added autoload-column index to all options-tables.'),
				];
				$autoload_index_addition = true;
			}
		}

		// Validate InnoDB conversion
		if ( $this->innodb_conversion['count'] === 0 ) {
			$result['innodb_conversion'] = [
				'type'    => 'success',
				'message' => sb__('All tables are already using InnoDB.'),
			];
			$innodb_conversion = null;
		} elseif ( count($this->innodb_conversion['fail']) > 0 ) {
			if ( count($this->innodb_conversion['fail']) === $this->innodb_conversion['count'] ) {
				$result['innodb_conversion'] = [
					'type'    => 'error',
					'message' => sb__('Could not convert tables to InnoDB.'),
				];
			} else {
				$failed_tables = array_map(function($table_name) {
					return [ sb__('Failed to convert the following tables to InnoDB:') => $table_name ];
				}, $this->innodb_conversion['fail']);
				$result['innodb_conversion'] = [
					'type'  => 'table',
					'table' => $failed_tables
				];
			}
			$innodb_conversion = false;
		} else {
			$result['innodb_conversion'] = [
				'type'    => 'success',
				'message' => sb__('All tables converted to InnoDB.'),
			];
			$innodb_conversion = true;
		}

		// All actions successful
		if ( $meta_value_index_addition === true && $autoload_index_addition === true && $innodb_conversion === true ) {
			return true;
		}

		// No action made
		if ( is_null($meta_value_index_addition) && is_null($autoload_index_addition) && is_null($innodb_conversion) ) {
			return null;
		}

		// We got one or more error
		return $result;

	}

	/**
	 * Handle table analyze cron.
	 */
	private function add_cron_handling() {
		$cron_key = 'servebolt_cron_hook_analyze_tables';
		add_action( $cron_key, [$this, 'analyze_tables'] );
		if ( ! wp_next_scheduled( $cron_key ) ) {
			wp_schedule_event( time(), 'daily', $cron_key );
		}
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
		if ( is_multisite() ) {
			sb_iterate_sites(function($site) {
				switch_to_blog( $site->blog_id );
				$this->remove_indexes();
				restore_current_blog();
			});
		} else {
			$this->remove_indexes();
		}
	}

	/**
	 * Revert optimizations.
	 */
	private function remove_indexes() {
		$this->remove_post_meta_index();
		$this->remove_options_autoload_index();
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
			sb_iterate_sites(function($site) use($post_meta_tables_with_post_meta_value_index) {
				switch_to_blog( $site->blog_id );
				if ( ! in_array($this->wpdb()->postmeta, $post_meta_tables_with_post_meta_value_index ) ) {
					$this->meta_value_index_addition['count']++;
					if ( $this->dry_run || $this->add_post_meta_index() ) {
						$this->out(sprintf( sb__('Added index to table "%s" on site %s (site ID %s)'), $this->wpdb()->postmeta, $this->blog_identification($site), $site->blog_id), 'success');
						$this->meta_value_index_addition['success'][] = $site->blog_id;
					} else {
						$this->out(sprintf( sb__('Could not add index to table "%s" on site %s (site ID %s)'), $this->wpdb()->postmeta, $this->blog_identification($site), $site->blog_id), 'error');
						$this->meta_value_index_addition['fail'][] = $site->blog_id;
					}
				}
				restore_current_blog();
			});
		} else {
			if ( ! in_array($this->wpdb()->postmeta, $post_meta_tables_with_post_meta_value_index ) ) {
				if ( $this->dry_run || $this->add_post_meta_index() ) {
					$this->out(sprintf(sb__('Added index to table "%s"'), $this->wpdb()->postmeta), 'success');
					$this->meta_value_index_addition = true;
				} else {
					$this->out(sprintf(sb__('Could not add index to table "%s"'), $this->wpdb()->postmeta), 'error');
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
			sb_iterate_sites(function($site) use ($options_tables_with_autoload_index) {
				switch_to_blog( $site->blog_id );
				if ( ! in_array($this->wpdb()->options, $options_tables_with_autoload_index ) ) {
					$this->autoload_index_addition['count']++;
					if ( $this->dry_run || $this->add_options_autoload_index() ) {
						$this->out(sprintf( sb__('Added index to table "%s" on site %s (site ID %s)'), $this->wpdb()->options, $this->blog_identification($site), $site->blog_id ), 'success');
						$this->autoload_index_addition['success'][] = $site->blog_id;
					} else {
						$this->out(sprintf( sb__('Could not add index to table "%" on site %s (site ID %s)'), $this->wpdb()->options, $this->blog_identification($site), $site->blog_id ), 'error');
						$this->autoload_index_addition['fail'][] = $site->blog_id;
					}
				}
				restore_current_blog();
			});
		} else {
			if ( ! in_array($this->wpdb()->options, $options_tables_with_autoload_index ) ) {
				if ( $this->dry_run || $this->add_options_autoload_index() ) {
					$this->out(sb__('Added index to table "options-table'), 'success');
					$this->autoload_index_addition = true;
				} else {
					$this->out(sb__('Could not add index to options-table'), 'error');
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
			sb_iterate_sites(function($site) use ($table_name, &$tables) {
				switch_to_blog($site->blog_id);
				$tables[] = $this->wpdb()->get_results("SHOW INDEX FROM {$this->wpdb()->prefix}{$table_name}");
				restore_current_blog();
			});
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
	 * Get table name by blog Id.
	 *
	 * @param $table
	 *
	 * @return mixed
	 */
	public function get_table_name($table) {
		return $this->wpdb()->{$table};
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
		$sql = $this->wpdb()->prepare("SELECT count(*) AS count FROM INFORMATION_SCHEMA.TABLES WHERE LOWER(engine) = %s AND TABLE_NAME = %s AND TABLE_SCHEMA = %s", $engine, $table_name, DB_NAME);
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
		return $this->wpdb()->get_results("SELECT engine, table_name FROM INFORMATION_SCHEMA.TABLES WHERE LOWER(engine) != 'innodb' AND TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME LIKE '{$this->wpdb()->prefix}%'");
	}

	/**
	 * Get all tables that are not using the InnoDB engine.
	 *
	 * @return mixed
	 */
	private function get_innodb_tables() {
		return $this->wpdb()->get_results("SELECT engine, table_name FROM INFORMATION_SCHEMA.TABLES WHERE LOWER(engine) = 'innodb' AND TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME LIKE '{$this->wpdb()->prefix}%'");
	}

	/**
	 * Attempt to convert all non-InnoDB tables to InnoDB.
	 */
	private function convert_tables_to_innodb() {
		$tables = $this->get_non_innodb_tables();
		if( is_array($tables) && ! empty($tables) ) {
			foreach ( $tables as $table ) {
				if ( ! isset($table->table_name) ) continue;
				$this->innodb_conversion['count']++;
				if ( $this->dry_run || $this->convert_table_to_innodb($table->table_name) ) {
					$this->out(sprintf(sb__('Converted table "%s" to InnoDB'), $table->table_name), 'success');
					$this->innodb_conversion['success'][] = $table->table_name;
				} else {
					$this->out(sprintf(sb__('Could not convert table "%s" to InnoDB'), $table->table_name), 'error');
					$this->innodb_conversion['fail'][] = $table->table_name;
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
	public function analyze_tables($cli = false) {
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
	 * Handle output.
	 *
	 * @param $string
	 * @param string $type
	 */
	private function out($string, $type = 'line') {
		if ( $this->cli ) {
			if ( $type == 'error' ) {
				WP_CLI::error($string, false);
			} else {
				WP_CLI::$type($string);
			}
		} else {
			$this->tasks[] = $string;
		}
	}

}
Servebolt_Optimize_DB::get_instance();
