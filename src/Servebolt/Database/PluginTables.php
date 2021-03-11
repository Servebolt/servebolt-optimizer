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

    /**
     * PluginTables constructor.
     * @param bool $initializeOnConstruct
     */
    public function __construct(bool $initializeOnConstruct = true)
    {
        if ($initializeOnConstruct) {
            $this->checkTables();
        }
    }

    /**
     * Check that tables exists, if not create them.
     */
    public function checkTables(): void
    {
        foreach($this->migrations as $migration => $baseTableName) {
            $tableName = $this->generateTableNameFromMigration($baseTableName);
            if (!$this->tableExists($tableName)) {
                $this->createTable($migration, $tableName);
            }
        }
    }

    public function deleteTables(): void
    {
        foreach($this->migrations as $migration => $baseTableName) {
            $tableName = $this->generateTableNameFromMigration($baseTableName);
            $this->deleteTable($tableName);
        }
    }

    public function deleteTable(string $tableName): void
    {
        global $wpdb;
        $sql = "DROP TABLE IF EXISTS `{$tableName}`";
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
    public function tableExists(string $tableName): bool
    {
        global $wpdb;
        return in_array($tableName, $wpdb->get_col($wpdb->prepare('SHOW TABLES LIKE %s', $tableName), 0), true);
    }

    /**
     * Check whether all tables are created like they should.
     *
     * @return bool
     */
    public function tablesExist(): bool
    {
        foreach($this->migrations as $migration => $baseTableName) {
            $tableName = $this->generateTableNameFromMigration($baseTableName);
            if (!$this->tableExists($tableName)) {
                return false;
            }
        }
        return true;
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
     * @return void
     */
    private function createTable(string $migration, string $tableName): void
    {
        $migrationFilePath = __DIR__ . '/migrations/' . $migration . '-migration.sql';
        $rawMigration = file_get_contents($migrationFilePath);
        $populatedMigration = $this->populateMigration($rawMigration, compact('tableName'));
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($populatedMigration);
    }
}
