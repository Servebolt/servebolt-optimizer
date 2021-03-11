<?php

namespace Servebolt\Optimizer\Database;

class PluginTables
{

    /**
     * @var array|string[] Array of migrations.
     */
    private array $migrations = [
        'queue' => 'sb_queue',
    ];

    public function __construct()
    {
        foreach($this->migrations as $migration => $baseTableName) {
            $tableName = $this->generateTableNameFromMigration($baseTableName);
            if (!$this->tableExists($tableName)) {
                $this->createTable($migration, $tableName);
            }
        }
    }

    public function deleteTable($tableName): void
    {
        global $wpdb;
        $sql = "DROP TABLE IF EXISTS $tableName;";
        $wpdb->query($sql);
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

    /**
     * @param string $tableName
     * @return bool
     */
    private function tableExists(string $tableName): bool
    {
        global $wpdb;
        $row = $wpdb->get_row("SELECT table_name FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = '{$tableName}'");
        return is_object($row)
            && isset($row->table_name)
            && $row->table_name === $tableName;
    }

    /**
     * @param string $migrationString
     * @param array $arguments
     * @return string
     */
    private function populateMigration(string $migrationString, array $arguments): string
    {
        $migrationString = str_replace('%table-name%', $arguments['tableName'], $migrationString);
        return $migrationString;
    }

    /**
     * @param string $migration
     * @param string $tableName
     * @return bool
     */
    private function createTable(string $migration, string $tableName): bool
    {
        $migrationFilePath = __DIR__ . '/migrations/' . $migration . '-migration.sql';
        $rawMigration = file_get_contents($migrationFilePath);
        $populatedMigration = $this->populateMigration($rawMigration, compact('tableName'));
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($populatedMigration);
        return $this->tableExists($tableName);
    }
}
