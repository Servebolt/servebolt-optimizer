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

    public function dropTable(): void
    {
        if ($tableName = $this->getTableName()) {
            global $wpdb;
            $sql = sprintf('DROP TABLE IF EXISTS %s;', $tableName);
            $wpdb->query($sql);
        }
    }

    private function getTableName(): ?string
    {
        global $wpdb;
        if (property_exists($this, 'tableName')) {
            return $wpdb->prefix . $this->tableName;
        }
        return null;
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
        if ($tableName = $this->getTableName()) {
            $sql = str_replace('%table-name%', $tableName, $sql);
        }
        $sql = str_replace('%prefix%', $wpdb->prefix, $sql);
        return $sql;
    }
}
