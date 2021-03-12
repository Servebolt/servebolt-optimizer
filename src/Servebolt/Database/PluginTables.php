<?php

namespace Servebolt\Optimizer\Database;

class PluginTables
{

    /**
     * @var array|string[] Array of migrations.
     */
    private $migrations = [
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
            $this->checkTable($migration);
        }
    }

    /**
     * Check if a table exist, if not create it.
     *
     * @param string $migration
     * @return bool|null
     */
    public function checkTable(string $migration): ?bool
    {
        if (!$baseTableName = $this->getBaseTableNameFromMigration($migration)) {
            return false;
        }
        $tableName = $this->generateTableNameWithPrefix($baseTableName);
        if (!$this->tableExists($tableName)) {
            $this->createTable($migration, $tableName);
        }
        return true;
    }

    public function deleteTables(): void
    {
        foreach($this->migrations as $migration) {
            $this->deleteTable($migration);
        }
    }

    /**
     * @param string $migration
     * @return bool|null
     */
    public function deleteTable(string $migration): ?bool
    {
        if (!$baseTableName = $this->getBaseTableNameFromMigration($migration)) {
            return false;
        }
        $tableName = $this->generateTableNameWithPrefix($baseTableName);
        global $wpdb;
        $sql = "DROP TABLE IF EXISTS `{$tableName}`";
        return $wpdb->query($sql);
    }

    /**
     * @param string $baseTableName
     * @return string
     */
    private function generateTableNameWithPrefix(string $baseTableName): string
    {
        global $wpdb;
        return $wpdb->prefix . $baseTableName;

    }

    /**
     * @param string $migration
     * @return false|mixed|string
     */
    private function getBaseTableNameFromMigration(string $migration)
    {
        if (!array_key_exists($migration, $this->migrations)) {
            return false;
        }
        return $this->migrations[$migration];
    }

    /**
     * Check if a table exists.
     *
     * @param string $migration
     * @return bool
     */
    public function tableExists(string $migration): bool
    {
        global $wpdb;
        if (!$baseTableName = $this->getBaseTableNameFromMigration($migration)) {
            return false;
        }
        $tableName = $this->generateTableNameWithPrefix($baseTableName);
        return in_array($tableName,
            $wpdb->get_col(
                $wpdb->prepare('SHOW TABLES LIKE %s', $tableName),
            0),
        true);
    }

    /**
     * Check whether all tables are created like they should.
     *
     * @return bool
     */
    public function tablesExist(): bool
    {
        foreach($this->migrations as $migration => $baseTableName) {
            if (!$this->tableExists($migration)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Populate migration with real table name etc.
     *
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
