<?php

namespace Servebolt\Optimizer\DatabaseOptimizer;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use WP_CLI;
use Servebolt\Optimizer\Cli\CliHelpers;
use Servebolt\Optimizer\Traits\Singleton;
use function WP_CLI\Utils\format_items as WP_CLI_FormatItems;
use function Servebolt\Optimizer\Helpers\iterateSites;

/**
 * Class DatabaseOptimizer
 *
 * This class facilitates the DB optimizations.
 */
class DatabaseOptimizer
{
    use Singleton;

	/**
	 * @var bool Whether we will do a dry run or not.
	 */
	private $dryRun = false;

	/**
	 * @var null Whether we added one or more post meta value indexes during the optimization.
	 */
	private $metaValueIndexAddition = null;

	/**
	 * @var null Whether we added one or more option autoload indexes during the optimization.
	 */
	private $autoloadIndexAddition = null;

	/**
	 * @var null Whether we converted one or more tables to InnoDB.
	 */
	private $innodbConversion = null;

	/**
	 * @var array The tasks done while running optimization.
	 */
	private $tasks = [];

	/**
	 * @var bool Whether we run this via CLI or not.
	 */
	private $cli = false;

    /**
     * @var array An array containing result from each optimization action (only used when retuning JSON).
     */
	private $actionOutput = [];

	/**
	 * DatabaseOptimizer constructor.
	 */
	private function __construct()
    {
		$this->addCronHandling();
	}

    /**
     * Set dry run boolean.
     *
     * @param bool $dryRun
     * @return $this
     */
	public function setDryRun(bool $dryRun)
    {
        $this->dryRun = $dryRun;
        return $this;
    }

	/**
	 * Run database optimization.
	 *
	 * @param bool $cli
	 *
	 * @return array
	 */
	public function optimizeDb(bool $cli = false)
    {
		$this->cli = $cli;
		if ($this->cli && !CliHelpers::returnJson()) {
			WP_CLI::line(__('Starting optimization...', 'servebolt-wp'));
		}
		$this->resetResultVariables();
		$this->optimizePostMetaTables();
		$this->optimizeOptionsTables();
		$this->convertTablesToInnodb();
		return $this->handleResult();
	}

	/**
	 * Set result variables to defaults.
	 */
	private function resetResultVariables()
    {
		$boilerplateArray = [
			'success' => [],
			'fail'    => [],
			'count'   => 0,
		];
		if (is_multisite()) {
			$this->metaValueIndexAddition = $boilerplateArray;
			$this->autoloadIndexAddition = $boilerplateArray;
			$this->innodbConversion = $boilerplateArray;
		} else {
			$this->metaValueIndexAddition = null;
			$this->autoloadIndexAddition = null;
			$this->innodbConversion = $boilerplateArray;
		}
	}

	/**
	 * @param $result
	 * @param $message
	 * @param bool $tasks
	 *
	 * @return array
	 */
	private function returnResult($result, $message, $tasks = false)
    {
		if ($this->cli) {
			$this->out($message, ($result ? 'success' : 'error'), [
			    'actions' => $this->actionOutput,
            ]);
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
	private function handleResult()
    {
		$result = $this->parseResult();

		if (is_null($result)) {
			// No changes made
			return $this->returnResult(true, __('No changes needed, database looks healthy, and everything is good!', 'servebolt-wp'));
		} elseif ($result === true) {
			// Changes made and they were done successfully
			return $this->returnResult(true, __('Nice, we got to do some changes to the database and it seems that we we\'re successful!', 'servebolt-wp'), $this->tasks);
		} else {
			// We did some changes, and got one or more errors
			if ($this->cli) {
			    if (CliHelpers::returnJson()) {

			        CliHelpers::printJson([
			            'success' => false,
                        'result' => $result,
                        'actions' => $this->actionOutput,
                    ]);

                } else {
                    WP_CLI::line(str_repeat('-', 20) . PHP_EOL . __('Summary:', 'servebolt-wp'));
                    foreach($result as $key => $value) {
                        switch ($value['type']) {
                            case 'success':
                                WP_CLI::success($value['message']);
                                break;
                            case 'error':
                                WP_CLI::error($value['message'], false);
                                break;
                            case 'table':
                                WP_CLI_FormatItems( 'table', $value['table'], array_keys(current($value['table'])));
                                break;
                        }
                    }
                }
			} else {
				return $this->returnResult(true, __('', 'servebolt-wp'), $this->tasks);
			}

		}
	}

	/**
	 * Check how the optimization resulted.
	 *
	 * @return bool
	 */
	private function parseResult()
    {

		$result = [];
		$metaValueIndexAddition = false;
		$autoloadIndexAddition = false;
		$innodbConversion = false;

		// Validate creation of meta_value-column index
		if (is_bool($this->metaValueIndexAddition ) || is_null($this->metaValueIndexAddition)) {
			if (is_null($this->metaValueIndexAddition)) {
				$result['meta_value_index_addition'] = [
					'type'    => 'success',
					'message' => __('Post meta table already have an index on the meta_value-column.', 'servebolt-wp'),
				];
				$metaValueIndexAddition = null;
			} elseif ( $this->metaValueIndexAddition === false ) {
				$result['meta_value_index_addition'] = [
					'type'    => 'error',
					'message' => __('Could not add meta_value-column index on post meta-table.', 'servebolt-wp'),
				];
				$metaValueIndexAddition = false;
			} else {
				$result['meta_value_index_addition'] = [
					'type'    => 'success',
					'message' => __('Added meta_value-column index to post meta-table.', 'servebolt-wp'),
				];
				$metaValueIndexAddition = true;
			}
		} elseif ( is_array($this->metaValueIndexAddition) ) {
			if ( $this->metaValueIndexAddition['count'] === 0 ) {
				$result['meta_value_index_addition'] = [
					'type'    => 'success',
					'message' => __('All post meta tables already has an index on the meta_value-column.', 'servebolt-wp'),
				];
				$metaValueIndexAddition = null;
			} elseif ( count($this->metaValueIndexAddition['fail']) > 0 ) {
				if ( count($this->metaValueIndexAddition['fail']) === $this->metaValueIndexAddition['count'] ) {
					$result['meta_value_index_addition'] = [
						'type'    => 'error',
						'message' => __('Failed to add meta_value-column index on all post meta-tables.', 'servebolt-wp'),
					];
				} else {
					$failedBlogUrls = array_map(function($blogId) {
						return get_site_url($blogId);
					}, $this->metaValueIndexAddition['fail']);
					$result['meta_value_index_addition'] = [
						'type'  => 'table',
						'table' => [ __('Failed to add meta_value-column index to post meta-tables on sites:', 'servebolt-wp') => $failedBlogUrls ]
					];
				}
				$metaValueIndexAddition = false;
			} else {
				$result['meta_value_index_addition'] = [
					'type'    => 'success',
					'message' => __('Added meta_value-column index to all post meta tables.', 'servebolt-wp'),
				];
				$metaValueIndexAddition = true;
			}
		}

		// Validate creation of autoload-column index
		if (is_bool($this->autoloadIndexAddition) || is_null($this->autoloadIndexAddition)) {
			if (is_null($this->autoloadIndexAddition)) {
				$result['autoload_index_addition'] = [
					'type'    => 'success',
					'message' => __('Options table already have an index on the autoload-column.', 'servebolt-wp'),
				];
				$autoloadIndexAddition = null;
			} elseif ($this->autoloadIndexAddition === false) {
				$result['autoload_index_addition'] = [
					'type'    => 'error',
					'message' => __('Could not add autoload-column index on options-table.', 'servebolt-wp'),
				];
				$autoloadIndexAddition = false;
			} else {
				$result['autoload_index_addition'] = [
					'type'    => 'success',
					'message' => __('Added autoload-column index to options-table.', 'servebolt-wp'),
				];
				$autoloadIndexAddition = true;
			}
		} elseif (is_array($this->autoloadIndexAddition)) {
			if ($this->autoloadIndexAddition['count'] === 0) {
				$result['autoload_index_addition'] = [
					'type' => 'success',
					'message' => __('All options tables already has an index on the autoload-column.', 'servebolt-wp'),
				];
				$autoloadIndexAddition = null;
			} elseif (count($this->autoloadIndexAddition['fail']) > 0) {
				if (count($this->autoloadIndexAddition['fail']) === $this->autoloadIndexAddition['count'] ) {
					$result['autoload_index_addition'] = [
						'type'    => 'error',
						'message' => __('Failed to add autoload-column index on all options-tables', 'servebolt-wp'),
					];
				} else {
					$failedBlogUrls = array_map(function($blogId) {
						return [ __('Failed to add autoload-column index to options tables on sites:', 'servebolt-wp') => get_site_url($blogId) ];
					}, $this->autoloadIndexAddition['fail']);
					$result['autoload_index_addition'] = [
						'type'  => 'table',
						'table' => $failedBlogUrls,
					];
				}
				$autoloadIndexAddition = false;
			} else {
				$result['autoload_index_addition'] = [
					'type'    => 'success',
					'message' => __('Added autoload-column index to all options tables.', 'servebolt-wp'),
				];
				$autoloadIndexAddition = true;
			}
		}

		// Validate InnoDB conversion
		if ($this->innodbConversion['count'] === 0) {
			$result['innodb_conversion'] = [
				'type'    => 'success',
				'message' => __('All tables are already using InnoDB.', 'servebolt-wp'),
			];
			$innodbConversion = null;
		} elseif (count($this->innodbConversion['fail']) > 0) {
			if (count($this->innodbConversion['fail']) === $this->innodbConversion['count']) {
				$result['innodb_conversion'] = [
					'type'    => 'error',
					'message' => __('Could not convert tables to InnoDB.', 'servebolt-wp'),
				];
			} else {
				$failedTables = array_map(function($tableName) {
					return [__('Failed to convert the following tables to InnoDB:', 'servebolt-wp') => $tableName];
				}, $this->innodbConversion['fail']);
				$result['innodb_conversion'] = [
					'type'  => 'table',
					'table' => $failedTables
				];
			}
			$innodbConversion = false;
		} else {
			$result['innodb_conversion'] = [
				'type'    => 'success',
				'message' => __('All tables converted to InnoDB.', 'servebolt-wp'),
			];
			$innodbConversion = true;
		}

		// All actions successful
		if ($metaValueIndexAddition === true && $autoloadIndexAddition === true && $innodbConversion === true) {
			return true;
		}

		// No action made
		if (is_null($metaValueIndexAddition) && is_null($autoloadIndexAddition) && is_null($innodbConversion)) {
			return null;
		}

		// We got one or more error
		return $result;

	}

	/**
	 * Handle table analyze cron.
	 */
	private function addCronHandling()
    {
		$cronKey = 'servebolt_cron_hook_analyze_tables';
		add_action($cronKey, [$this, 'analyzeTables']);
		if (!wp_next_scheduled($cronKey)) {
			wp_schedule_event(time(), 'daily', $cronKey);
		}
	}

	/**
	 * Generate a name for the site.
	 *
	 * @param $site
	 *
	 * @return string
	 */
	private function blogIdentification($site): string
    {
		return trim($site->domain . $site->path, '/');
	}

	/**
	 * Remove table optimization measures.
	 */
	public function deoptimizeIndexedTables()
    {
		if (is_multisite()) {
            iterateSites(function($site) {
				$this->removeIndexes();
			}, true);
		} else {
			$this->removeIndexes();
		}
	}

	/**
	 * Revert optimizations.
	 */
	private function removeIndexes()
    {
		try {
			$removePostMetaIndex = $this->removePostMetaIndex();
			$removeOptionsAutoloadIndex = $this->removeOptionsAutoloadIndex();
			return $removePostMetaIndex && $removeOptionsAutoloadIndex;
		} catch (Exception $e) {
			return false;
		}
	}

	/**
	 * Attempt to convert all non-InnoDB tables to InnoDB.
	 */
	public function convertTablesToNonInnodb()
    {
		$tables = $this->getInnodbTables();
		if( is_array($tables) && ! empty($tables) ) {
			foreach ($tables as $table) {
				if (!isset($table->table_name)) {
				    continue;
                }
				$this->convertTableToMyisam($table->table_name);
			}
		}
	}

	/**
	 * Optimize post meta table by adding an index to the value-column.
	 */
	private function optimizePostMetaTables()
    {
		$postMetaTablesWithPostMetaValueIndex = $this->tablesWithIndexOnColumn('postmeta', 'meta_value');
		if (is_multisite()) {
            iterateSites(function($site) use($postMetaTablesWithPostMetaValueIndex) {
				if (!in_array($this->wpdb()->postmeta, $postMetaTablesWithPostMetaValueIndex)) {
					$this->metaValueIndexAddition['count']++;
					if ($this->dryRun || $this->addPostMetaIndex()) {
					    $message = sprintf( __('Added index to table "%s" on site %s (site ID %s)'), $this->wpdb()->postmeta, $this->blogIdentification($site), $site->blog_id);
					    if (CliHelpers::returnJson()) {
                            $this->actionOutput[] = [
                                'success' => true,
                                'message' => $message,
                            ];
                        } else {
                            $this->out($message, 'success');
                        }
						$this->metaValueIndexAddition['success'][] = $site->blog_id;
					} else {
					    $errorMessage = sprintf( __('Could not add index to table "%s" on site %s (site ID %s)'), $this->wpdb()->postmeta, $this->blogIdentification($site), $site->blog_id);
                        if (CliHelpers::returnJson()) {
                            $this->actionOutput[] = [
                                'success' => false,
                                'message' => $errorMessage,
                            ];
                        } else {
                            $this->out($errorMessage, 'error');
                        }
						$this->metaValueIndexAddition['fail'][] = $site->blog_id;
					}
				}
			}, true);
		} else {
			if (!in_array($this->wpdb()->postmeta, $postMetaTablesWithPostMetaValueIndex)) {
				if ($this->dryRun || $this->addPostMetaIndex()) {
				    $message = sprintf(__('Added index to table "%s"', 'servebolt-wp'), $this->wpdb()->postmeta);
                    if (CliHelpers::returnJson()) {
                        $this->actionOutput[] = [
                            'success' => true,
                            'message' => $message,
                        ];
                    } else {
                        $this->out($message, 'success');
                    }
					$this->metaValueIndexAddition = true;
				} else {
				    $errorMessage = sprintf(__('Could not add index to table "%s"', 'servebolt-wp'), $this->wpdb()->postmeta);
                    if (CliHelpers::returnJson()) {
                        $this->actionOutput[] = [
                            'success' => false,
                            'message' => $errorMessage,
                        ];
                    } else {
                        $this->out($errorMessage, 'error');
                    }
					$this->metaValueIndexAddition = false;
				}
			}
		}
	}

	/**
	 * Optimize the options table by adding an index to the autoload-column.
	 */
	private function optimizeOptionsTables()
    {
		$optionsTablesWithAutoloadIndex = $this->tablesWithIndexOnColumn('options', 'autoload');
		if (is_multisite()) {
            iterateSites(function($site) use ($optionsTablesWithAutoloadIndex) {
				if (!in_array($this->wpdb()->options, $optionsTablesWithAutoloadIndex)) {
					$this->autoloadIndexAddition['count']++;
					if ( $this->dryRun || $this->addAptionsAutoloadIndex() ) {
					    $message = sprintf(__('Added index to table "%s" on site %s (site ID %s)'), $this->wpdb()->options, $this->blogIdentification($site), $site->blog_id);
					    if (CliHelpers::returnJson()) {
                            $this->actionOutput[] = [
                                'success' => true,
                                'message' => $message,
                            ];
                        } else {
                            $this->out($message, 'success');
                        }
						$this->autoloadIndexAddition['success'][] = $site->blog_id;
					} else {
					    $errorMessage = sprintf(__('Could not add index to table "%" on site %s (site ID %s)'), $this->wpdb()->options, $this->blogIdentification($site), $site->blog_id);
					    if (CliHelpers::returnJson()) {
                            $this->actionOutput[] = [
                                'success' => false,
                                'message' => $errorMessage,
                            ];
                        } else {
                            $this->out($errorMessage, 'error');
                        }
						$this->autoloadIndexAddition['fail'][] = $site->blog_id;
					}
				}
			}, true);
		} else {
			if (!in_array($this->wpdb()->options, $optionsTablesWithAutoloadIndex)) {
				if ($this->dryRun || $this->addOptionsAutoloadIndex()) {
				    $message = __('Added index to table "options table', 'servebolt-wp');
				    if (CliHelpers::returnJson()) {
                        $this->actionOutput[] = [
                            'success' => true,
                            'message' => $message,
                        ];
                    } else {
                        $this->out($message, 'success');
                    }
					$this->autoloadIndexAddition = true;
				} else {
				    $errorMessage = __('Could not add index to options table', 'servebolt-wp');
                    if (CliHelpers::returnJson()) {
                        $this->actionOutput[] = [
                            'success' => false,
                            'message' => $errorMessage,
                        ];
                    } else {
                        $this->out($errorMessage, 'error');
                    }
					$this->autoloadIndexAddition = false;
				}

			}
		}
	}

	/**
	 * Get tables that has an index on a given column.
	 *
	 * @param string $tableName
	 * @param string $columnName
	 *
	 * @return array
	 */
	private function tablesWithIndexOnColumn(string $tableName, string $columnName)
    {
		$tables = [];
		if (is_multisite()) {
            iterateSites(function($site) use ($tableName, &$tables) {
				$tables[] = $this->wpdb()->get_results("SHOW INDEX FROM {$this->wpdb()->prefix}{$tableName}");
			}, true);
		} else {
			$tables[] = $this->wpdb()->get_results("SHOW INDEX FROM {$this->wpdb()->prefix}{$tableName}");
		}

		$tablesWithIndexOnColumn = [];
		foreach ($tables as $table) {
			foreach ($table as $index) {
				if (!isset($index->Column_name)) {
				    continue;
                }
				if ($index->Column_name == $columnName) {
					$tablesWithIndexOnColumn[] = $index->Table;
				}
			}
		}

		return $tablesWithIndexOnColumn;
	}

	/**
	 * Add options autoload index to the options table.
	 *
	 * @param null|int $blogId
	 *
	 * @return bool
	 */
	public function addOptionsAutoloadIndex(?int $blogId = null)
    {
		if ($blogId) {
		    switch_to_blog($blogId);
        }
		$this->safeQuery("ALTER TABLE {$this->wpdb()->options} ADD INDEX(autoload)");
		$result = $this->tableHasIndex($this->wpdb()->options, 'autoload');
		if ($blogId) {
		    restore_current_blog();
        }
		return $result;
	}

	/**
	 * Add options autoload index to the options table.
	 *
	 * @param null|int $blogId
	 *
	 * @return bool
	 */
	public function removeOptionsAutoloadIndex(?int $blogId = null)
    {
		if ($blogId) {
		    switch_to_blog($blogId);
        }
		$this->safeQuery("ALTER TABLE {$this->wpdb()->options} DROP INDEX `autoload`");
		$result = !$this->tableHasIndex($this->wpdb()->options, 'autoload');
		if ($blogId) {
		    restore_current_blog();
        }
		return $result;
	}

	/**
	 * Add post meta value index to the post meta table.
	 *
	 * @param null|int $blogId
	 *
	 * @return bool
	 */
	public function addPostMetaIndex(?int $blogId = null)
    {
		if ($blogId) {
		    switch_to_blog($blogId);
        }
		$this->safeQuery("ALTER TABLE {$this->wpdb()->postmeta} ADD INDEX `sbpmv` (`meta_value`(10))");
		$result = $this->tableHasIndex($this->wpdb()->postmeta, 'sbpmv');
		if ($blogId) {
		    restore_current_blog();
        }
		return $result;
	}

	/**
	 * Remove post meta value index to the post meta table.
	 *
	 * @param null|int $blogId
	 *
	 * @return bool
	 */
	public function removePostMetaIndex(?int $blogId = null)
    {
		if ($blogId) {
		    switch_to_blog($blogId);
        }
		$this->safeQuery("ALTER TABLE {$this->wpdb()->postmeta} DROP INDEX `sbpmv`");
		$result = ! $this->tableHasIndex($this->wpdb()->postmeta, 'sbpmv');
		if ($blogId) {
		    restore_current_blog();
        }
		return $result;
	}

	/**
	 * Get table name by blog Id.
	 *
	 * @param int $blogId
	 * @param string $table
	 *
	 * @return mixed
	 */
	public function getTableNameByBlogId($blogId, $table)
    {
		switch_to_blog($blogId);
		$tableName = $this->wpdb()->{$table};
		restore_current_blog();
		return $tableName;
	}

	/**
	 * Get table name by blog Id.
	 *
	 * @param $table
	 *
	 * @return mixed
	 */
	public function getTableName($table)
    {
		return $this->wpdb()->{$table};
	}

	/**
	 * Check if a table has an index.
	 *
	 * @param string $tableName
	 * @param string $indexName
	 *
	 * @return bool
	 */
	public function tableHasIndex(string $tableName, string $indexName): bool
    {
		$indexes = $this->wpdb()->get_results("SHOW INDEX FROM {$tableName}");
		if (is_array($indexes)) {
			foreach ($indexes as $index) {
				if (!isset($index->Key_name)) {
                    continue;
                }
				if ($index->Key_name == $indexName) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Check if a table has an index.
	 *
	 * @param string $tableName
	 * @param string $columnName
	 *
	 * @return bool
	 */
	public function tableHasIndexOnColumn(string $tableName, string $columnName)
    {
		$indexes = $this->wpdb()->get_results("SHOW INDEX FROM {$tableName}");
		if (is_array($indexes)) {
			foreach ($indexes as $index) {
				if (!isset($index->Column_name)) {
				    continue;
                }
				if ($index->Column_name == $columnName) {
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
	private function wpdb()
    {
		return $GLOBALS['wpdb'];
	}

	/**
	 * Convert a table to InnoDB.
	 *
	 * @param string $tableName
	 *
	 * @return bool
	 */
	public function convertTableToInnodb(string $tableName)
    {
		$this->safeQuery("ALTER TABLE {$tableName} ENGINE = InnoDB");
		return $this->tableIsInnodb($tableName);
	}

	/**
	 * Run SQL-query and suppress errors (we run queries best effort, and inspect the result after query is done).
	 *
	 * @param $query
	 *
	 * @return mixed
	 */
	private function safeQuery($query)
    {
		$this->wpdb()->suppress_errors();
		$query = $this->wpdb()->query($query);
		$this->wpdb()->suppress_errors(false);
		return $query;
	}

	/**
	 * Convert a table for MyISAM.
	 *
	 * @param string $tableName
	 *
	 * @return bool
	 */
	public function convertTableToMyisam(string $tableName)
    {
		if ($this->tableHasEngine($tableName, 'myisam')) {
            return true;
        }
		try {
			$this->safeQuery("ALTER TABLE " . $tableName . " ENGINE = MyISAM");
			return $this->tableHasEngine($tableName, 'myisam');
		} catch (Exception $e) {
			return false;
		}
	}

	/**
	 * Check if a table is using a given engine.
	 *
	 * @param string $tableName
	 * @param string $engineToCheck
	 *
	 * @return bool
	 */
	public function tableHasEngine(string $tableName, string $engineToCheck)
    {
		$tableEngine = $this->getTableEngine($tableName);
		return $tableEngine == $engineToCheck;
	}

	/**
	 * Get the database engine of a table.
	 *
	 * @param string $tableName
	 *
	 * @return mixed
	 */
	private function getTableEngine(string $tableName)
    {
		$sql = $this->wpdb()->prepare("SELECT LOWER(engine) AS engine FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = %s AND TABLE_SCHEMA = %s LIMIT 1", $tableName, DB_NAME);
		return $this->wpdb()->get_var($sql);
	}

	/**
	 * Check if a table is using the InnoDB engine.
	 *
	 * @param string $tableName
	 *
	 * @return bool
	 */
	public function tableIsInnodb(string $tableName)
    {

		return $this->tableHasEngine($tableName, 'innodb');
	}

	/**
	 * Get all tables that are not using the InnoDB engine.
	 *
	 * @return mixed
	 */
	private function getNonInnodbTables()
    {
		return $this->wpdb()->get_results("SELECT engine, table_name FROM INFORMATION_SCHEMA.TABLES WHERE LOWER(engine) != 'innodb' AND TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME LIKE '{$this->wpdb()->prefix}%'");
	}

	/**
	 * Get all tables that are not using the InnoDB engine.
	 *
	 * @return mixed
	 */
	private function getInnodbTables()
    {
		return $this->wpdb()->get_results("SELECT engine, table_name FROM INFORMATION_SCHEMA.TABLES WHERE LOWER(engine) = 'innodb' AND TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME LIKE '{$this->wpdb()->prefix}%'");
	}

	/**
	 * Attempt to convert all non-InnoDB tables to InnoDB.
	 */
	private function convertTablesToInnodb()
    {
		$tables = $this->getNonInnodbTables();
		if(is_array($tables) && ! empty($tables)) {
			foreach ( $tables as $table ) {
				if (!isset($table->table_name)) {
                    continue;
                }
				$this->innodbConversion['count']++;
				if ($this->dryRun || $this->convertTableToInnodb($table->table_name)) {
				    $message = sprintf(__('Converted table "%s" to InnoDB', 'servebolt-wp'), $table->table_name);
				    if (CliHelpers::returnJson()) {
                        $this->actionOutput[] = [
                            'success' => true,
                            'message' => $message,
                        ];
                    } else {
                        $this->out($message, 'success');
                    }
                    $this->innodbConversion['success'][] = $table->table_name;
				} else {
				    $errorMessage = sprintf(__('Could not convert table "%s" to InnoDB', 'servebolt-wp'), $table->table_name);
				    if (CliHelpers::returnJson()) {
                        $this->actionOutput[] = [
                            'success' => false,
                            'message' => $errorMessage,
                        ];
                    } else {
                        $this->out($errorMessage, 'error');
                    }
					$this->innodbConversion['fail'][] = $table->table_name;
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
	public function analyzeTables($cli = false)
    {
		$this->analyzeTablesQuery();
		if (is_multisite()) {
			$this->analyzeTable($this->wpdb()->sitemeta);
			$siteBlogIds = $this->wpdb()->get_col($this->wpdb()->prepare("SELECT blog_id FROM {$this->wpdb()->blogs} where blog_id > 1"));
			foreach ($siteBlogIds AS $blogId) {
				switch_to_blog($blogId);
				$this->analyzeTablesQuery();
			}
			restore_current_blog();
		}
		if ($cli) {
            return true;
        }
	}

	/**
	 * Execute table analysis query.
	 *
	 * @param bool|wpdb $wpdb
	 */
	private function analyzeTablesQuery($wpdb = false)
    {
		if (!$wpdb ) {
		    $wpdb = $this->wpdb();
        }
		$this->analyzeTable($wpdb->posts);
		$this->analyzeTable($wpdb->postmeta);
		$this->analyzeTable($wpdb->options);
	}

	/**
	 * Analyze table.
	 *
	 * @param string $tableName
	 */
	private function analyzeTable(string $tableName): void
    {
		$this->wpdb()->query("ANALYZE TABLE {$tableName}");
	}

	/**
	 * Handle output.
	 *
	 * @param $string
	 * @param string $type
	 * @param array $additionalData
	 */
	private function out($string, string $type = 'line', array $additionalData = []): void
    {
		if ($this->cli) {
		    switch ($type) {
                case 'error':
                    if (CliHelpers::returnJson()) {
                        CliHelpers::printJson(array_merge($additionalData, [
                            'success' => false,
                            'message' => $string,
                        ]));
                    } else {
                        WP_CLI::error($string, false);
                    }
                    break;
                case 'success':
                    if (CliHelpers::returnJson()) {
                        CliHelpers::printJson(array_merge($additionalData, [
                            'success' => true,
                            'message' => $string,
                        ]));
                    } else {
                        WP_CLI::success($string);
                    }
                    break;
                default:
                    if (CliHelpers::returnJson()) {
                        CliHelpers::printJson(array_merge($additionalData, [
                            'message' => $string,
                        ]));
                    } else {
                        WP_CLI::$type($string);
                    }
                    break;
            }
		} else {
			$this->tasks[] = $string;
		}
	}

}
