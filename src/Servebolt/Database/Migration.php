<?php

namespace Servebolt\Optimizer\Database;

/**
 * Class Migration
 * @package Servebolt\Optimizer\Database
 */
class Migration
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
     * Populate variables in SQL-query.
     *
     * @param string $sql
     * @return string
     */
    private function populateSql(string $sql): string
    {
        global $wpdb;
        if (property_exists($this, 'tableName')) {
            $sql = str_replace('%table-name%', $wpdb->prefix . $this->tableName, $sql);
        }
        $sql = str_replace('%prefix%', $wpdb->prefix, $sql);
        return $sql;
    }
}
