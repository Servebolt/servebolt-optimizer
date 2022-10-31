<?php

namespace Servebolt\Optimizer\Utils\DatabaseMigration;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Class AbstractMigration
 * @package Servebolt\Optimizer\Utils\DatabaseMigration
 */
abstract class AbstractMigration
{

    /**
     * MySQL storage engine.
     *
     * @var string
     */
    protected $storageEngine = 'InnoDB';

    /**
     * Whether to use the function "dbDelta" when running queries.
     *
     * @var bool
     */
    protected $useDbDelta = true;

    /**
     * Run MySQL-query.
     *
     * @param string $sql
     */
    protected function runSql(string $sql): void
    {
        $sql = $this->populateSql($sql);
        error_log($sql);
        if ($this->useDbDelta) {
            $this->runDbDeltaQuery($sql);
        } else {
            $this->runWpdbQuery($sql);
        }        
    }

    /**
     * Run query using the dbDelta-function.
     *
     * @param string $sql
     */
    private function runDbDeltaQuery(string $sql): void
    {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Run query using the WPDB-instance.
     *
     * @param string $sql
     */
    private function runWpdbQuery(string $sql): void
    {
        global $wpdb;
        $wpdb->query($sql);
    }

    /**
     * Drop a table.
     */
    public function dropTable(): void
    {
        if ($tableName = $this->getTableNameWithPrefix()) {
            $sql = sprintf('DROP TABLE IF EXISTS %s;', $tableName);
            $this->runWpdbQuery($sql);
        }
    }

    /**
     * Populate variables in SQL-query.
     *
     * @param string $sql
     * @return string
     */
    private function populateSql(string $sql): string
    {
        global $wpdb;
        if ($tableName = $this->getTableNameWithPrefix()) {
            $sql = str_replace('%table-name%', $tableName, $sql);
        }

        $storageEngine = apply_filters('sb_optimizer_migration_mysql_charset', $this->storageEngine);

        $sql = str_replace('%prefix%', $wpdb->prefix, $sql);
        $sql = str_replace('%storage-engine%', $storageEngine, $sql);
        $sql = str_replace('%charset-collate%', $wpdb->get_charset_collate(), $sql);

        return $sql;
    }

    /**
     * Get full table name, including prefix.
     *
     * @return string|null
     */
    public function getTableNameWithPrefix(): ?string
    {
        global $wpdb;
        if (property_exists($this, 'tableName')) {
            return $wpdb->prefix . $this->tableName;
        }
        return null;
    }
}
