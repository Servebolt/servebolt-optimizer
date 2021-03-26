<?php

namespace Servebolt\Optimizer\Database;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Class AbstractMigration
 * @package Servebolt\Optimizer\Database
 */
abstract class AbstractMigration
{
    /**
     * Run MySQL-query.
     *
     * @param string $sql
     */
    protected function runSql(string $sql): void
    {
        $sql = $this->populateSql($sql);
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Drop a table.
     */
    public function dropTable(): void
    {
        if ($tableName = $this->getTableNameWithPrefix()) {
            global $wpdb;
            $sql = sprintf('DROP TABLE IF EXISTS %s;', $tableName);
            $wpdb->query($sql);
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

        $mysqlEngine = apply_filters('sb_optimizer_migration_mysql_charset', 'InnoDB');
        $mysqlCharset = apply_filters('sb_optimizer_migration_mysql_charset', $wpdb->charset);
        $mysqlCharsetCollate = apply_filters('sb_optimizer_migration_mysql_charset_collate', $wpdb->collate);

        if (!$mysqlCharset) {
            $mysqlCharset = apply_filters('sb_optimizer_migration_mysql_charset_fallback', 'utf8mb4');
        }
        if (!$mysqlCharsetCollate) {
            $mysqlCharsetCollate = apply_filters('sb_optimizer_migration_mysql_charset_collate_fallback', 'utf8mb4_unicode_520_ci');
        }

        $sql = str_replace('%prefix%', $wpdb->prefix, $sql);
        $sql = str_replace('%mysql-engine%', $mysqlEngine, $sql);
        $sql = str_replace('%charset%', $mysqlCharset, $sql);
        $sql = str_replace('%charset-collate%', $mysqlCharsetCollate, $sql);
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
