<?php

namespace Servebolt\Optimizer\Database;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Class Migration
 * @package Servebolt\Optimizer\Database
 */
abstract class Migration
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
        $sql = str_replace('%prefix%', $wpdb->prefix, $sql);
        return $sql;
    }

    /**
     * Get full table name, including prefix.
     *
     * @return string|null
     */
    private function getTableNameWithPrefix(): ?string
    {
        global $wpdb;
        if (property_exists($this, 'tableName')) {
            return $wpdb->prefix . $this->tableName;
        }
        return null;
    }
}
