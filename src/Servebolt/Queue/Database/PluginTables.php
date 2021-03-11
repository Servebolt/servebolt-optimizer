<?php

namespace Servebolt\Optimizer\Database;

class PluginTables
{

    private array $migrations = [
        'queue' => 'sb_queue',
    ];

    public function __construct()
    {
        foreach($this->migrations as $migration => $baseTableName) {
            $tableName = $this->generateTableNameFromMigration($baseTableName);
            if ($this->tableExists($tableName)) {
                $this->createTable($migration, $tableName);
            }
        }
        // TODO: Check that tables exists
        // TODO: Create tables that do not exist
    }

    public function deleteTable($tableName)
    {
        // TODO: Delete table
    }

    /**
     * @param string $baseTableName
     * @return string
     */
    private function generateTableNameFromMigration(string $baseTableName): string
    {
        global $wpdb;
        return $wpdb->prefix . $baseTableName;

    }

    private function tableExists(string $tableName): boolean
    {
        // TODO: Check if table exists
    }

    private function createTable(string $migration, string $tableName): boolean
    {
        // TODO: Create table from migration
    }
}
