<?php

namespace Servebolt\Optimizer\Database;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use function Servebolt\Optimizer\Helpers\deleteOption;
use function Servebolt\Optimizer\Helpers\getOption;
use function Servebolt\Optimizer\Helpers\updateOption;

/**
 * Class Migration
 * @package Servebolt\Optimizer\Database
 */
class MigrationRunner
{

    /**
     * @var string The last version we migrated to.
     */
    private $currentMigratedVersion;

    /**
     * @var string The current version of the plugin.
     */
    private $currentPluginVersion;

    /**
     * @var string The direction of the migration.
     */
    private $migrationDirection;

    /**
     * @var bool Whether to respect lower version constraint when selecting migrations.
     */
    private $ignoreLowerConstraint = false;

    /**
     * @var bool Whether to respect upper version constraint when selecting migrations.
     */
    private $ignoreUpperConstraint = false;

    /**
     * @var bool Whether to run pre/post methods on migration.
     */
    private $runPrePostMethods = true;

    /**
     * Check if we need to migrate, based on previous migration constraint.
     *
     * @param bool $skipAction Whether to run the migration check right away, and skip the action-step.
     */
    public function init(bool $skipAction = false): void
    {
        if ($skipAction) {
            $this->maybeRunMigrations();
        } else {
            add_action('admin_init', [$this, 'maybeRunMigrations']);
        }
    }

    /**
     * Run migration.
     *
     * @param bool $skipAction Whether to run the migration check right away, and skip the action-step.
     */
    public static function run(bool $skipAction = false): void
    {
        (new self)->init($skipAction);
    }

    /**
     * Will roll all migration back to zero, then forward again to the constraint of the current version of the plugin.
     */
    public static function migrateFresh(): void
    {
        (new self)->rollbackToZero();
        (new self)->rollbackFromZero();
    }

    /**
     * Will roll migrations forward to the constraint of the current version of the plugin.
     */
    public static function migrate(): void
    {
        (new self)->rollbackFromZero();
    }

    /**
     * Will roll all migration back to zero, effectively deleting all traces of the plugin in the database.
     */
    public static function cleanup(): void
    {
        (new self)->rollbackToZero();
    }

    /**
     * Running all migrations from zero to the
     */
    public function rollbackFromZero()
    {
        $this->runPrePostMethods = false;
        //$this->ignoreUpperConstraint = true;
        $this->migrationDirection = 'up';
        if ($migrations = $this->resolveMigrations()) {
            foreach ($migrations as $migration) {
                $this->runMigration($migration);
            }
        }
        $this->updateMigratedVersion();
    }

    /**
     * Rollback from current migration to zero, effectively deleting all traces of the plugin in the database.
     */
    public function rollbackToZero()
    {
        $this->runPrePostMethods = false;
        $this->ignoreLowerConstraint = true;
        $this->migrationDirection = 'down';
        if ($migrations = $this->resolveMigrations()) {
            foreach ($migrations as $migration) {
                $this->runMigration($migration);
            }
        }
        $this->clearMigratedVersion();
    }

    private function setCurrentVersions(): void
    {
        $this->currentMigratedVersion = $this->getCurrentMigratedVersion();
        $this->currentPluginVersion = $this->getCurrentPluginVersion();
    }

    public function maybeRunMigrations(): void
    {
        $this->setCurrentVersions();
        if ($this->currentMigratedVersion !== $this->currentPluginVersion) {
            $this->doMigration();
        }
    }

    private function doMigration(): void
    {
        $this->migrationDirection = $this->getMigrationDirection();
        if ($migrations = $this->resolveMigrations()) {
            foreach ($migrations as $migration) {
                $this->runMigration($migration);
            }
        }
        $this->updateMigratedVersion();
    }

    private function getAllMigrationFiles(): array
    {
        return glob(__DIR__ . '/Migrations/*.php');
    }

    /**
     * Resolve migrations, based on current version constraints.
     *
     * @return array
     */
    private function resolveMigrations(): array
    {
        $migrations = [];
        foreach($this->getAllMigrationFiles() as $migrationFile) {
            $fileNameParts = explode('_', basename($migrationFile, '.php'));
            if (count($fileNameParts) !== 2) {
                continue;
            }
            list($migrationNumber, $migrationName) = $fileNameParts;
            $className = '\\Servebolt\\Optimizer\\Database\\Migrations\\' . $migrationName;
            require_once $migrationFile;
            $version = $className::$version;
            if ($this->shouldRunMigration($version)) {
                $migrations[$migrationNumber] = $className;
            }
        }

        if ($this->migrationDirection === 'down') {
            krsort($migrations);
        } else {
            ksort($migrations);
        }

        return $migrations;
    }

    /**
     * Run specified migration.
     *
     * @param string $migrationClass
     */
    private function runMigration(string $migrationClass): void
    {
        $instance = new $migrationClass;
        $migrationMethod = $this->migrationDirection;

        if (method_exists($instance, $migrationMethod)) {
            if ($this->runPrePostMethods) {
                if (method_exists($instance, 'preMigration')) {
                    $instance->preMigration();
                }
                $preMethod = 'pre' . ucfirst($migrationMethod) . 'Migration';
                if (method_exists($instance, $preMethod)) {
                    $instance->{$preMethod}();
                }
            }

            $instance->{$migrationMethod}();

            if ($this->runPrePostMethods) {
                if (method_exists($instance, 'postMigration')) {
                    $instance->postMigration();
                }
                $postMethod = 'post' . ucfirst($migrationMethod) . 'Migration';
                if (method_exists($instance, $postMethod)) {
                    $instance->{$postMethod}();
                }
            }
        }
    }

    /**
     * Check whether given migration should be ran based on the plugin version defined in the migration, based on the constraint of the current plugin version and the previously migration version number.
     *
     * @param $migrationVersion
     * @return bool
     */
    private function shouldRunMigration($migrationVersion): bool
    {
        if (
            $this->migrationDirection == 'down'
            && (
                $this->ignoreLowerConstraint
                || (
                    $migrationVersion > $this->currentPluginVersion
                    && $migrationVersion <= $this->currentMigratedVersion
                )
            )
        ) {
            return true;
        } elseif (
            $this->migrationDirection == 'up'
            && (
                $this->ignoreUpperConstraint
                || (
                    $migrationVersion <= $this->currentPluginVersion
                    && $migrationVersion > $this->currentMigratedVersion
                )
            )
        ) {
            return true;
        } else {
            return false;
        }
    }

    private function getMigrationDirection(): string
    {
        return version_compare($this->currentMigratedVersion, $this->currentPluginVersion, '<') ? 'up' : 'down';
    }

    public function updateMigratedVersion($version = null): void
    {
        if (!$version) {
            $version = $this->getCurrentPluginVersion();
        }
        $this->currentMigratedVersion =  $version;
        updateOption('migration_version', $version);
    }

    public function clearMigratedVersion(): void
    {
        $this->currentMigratedVersion =  null;
        deleteOption('migration_version');
    }

    public function getCurrentMigratedVersion(): string
    {
        return getOption('migration_version');
    }

    public function getCurrentPluginVersion(bool $ignoreBetaVersion = true): string
    {
        $pluginData = get_plugin_data(SERVEBOLT_PLUGIN_FILE);
        if ($ignoreBetaVersion) {
            return preg_replace('/(.+)-(.+)/', '$1', $pluginData['Version']);
        }
        return $pluginData['Version'];
    }
}
