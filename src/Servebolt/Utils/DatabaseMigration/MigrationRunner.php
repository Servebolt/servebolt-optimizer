<?php

namespace Servebolt\Optimizer\Utils\DatabaseMigration;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use function Servebolt\Optimizer\Helpers\getCurrentPluginVersion;
use function Servebolt\Optimizer\Helpers\iterateSites;
use function Servebolt\Optimizer\Helpers\deleteOption;
use function Servebolt\Optimizer\Helpers\deleteSiteOption;
use function Servebolt\Optimizer\Helpers\getOption;
use function Servebolt\Optimizer\Helpers\getSiteOption;
use function Servebolt\Optimizer\Helpers\tableExists;
use function Servebolt\Optimizer\Helpers\updateOption;
use function Servebolt\Optimizer\Helpers\updateSiteOption;

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
     * @var string The options key used to store migration version.
     */
    private $migrationVersionOptionsKey = 'migration_version';

    /**
     * @var null|int The Id of the site we want to interact with.
     */
    private $siteId;

    /**
     * @return string
     */
    private function migrationVersionOptionsKey(): string
    {
        return apply_filters('sb_optimizer_migration_version_options_key', $this->migrationVersionOptionsKey);
    }

    /**
     * If a site is created we need to run all our migrations for that site.
     *
     * @param $siteId
     * @return void
     */
    public static function handleNewSite($siteId): void
    {
        $instance = new self;
        $instance->siteId = $siteId;
        $instance->migrateFromZero(false);
        $instance->siteId = null;
    }

    /**
     * If a site is deleted we need to roll back all our migrations for that site.
     *
     * @param $siteId
     * @return void
     */
    public static function handleDeletedSite($siteId): void
    {
        $instance = new self;
        $instance->siteId = $siteId;
        $instance->rollbackToZero(false);
        $instance->siteId = null;
    }

    /**
     * Run migration.
     */
    public static function run(): void
    {
        (new self)->maybeRunMigrations();
    }

    /**
     * Will roll all migration back to zero, then forward again to the constraint of the current version of the plugin.
     */
    public static function migrateFresh(): void
    {
        (new self)->rollbackToZero();
        (new self)->migrateFromZero();
    }

    /**
     * Will roll migrations forward to the constraint of the current version of the plugin.
     */
    public static function migrate(): void
    {
        (new self)->migrateFromZero();
    }

    /**
     * Will roll all migration back to zero, effectively deleting all traces of the plugin in the database.
     */
    public static function cleanup(): void
    {
        (new self)->rollbackToZero();
    }

    /**
     * Running all migrations from zero to the latest migration.
     *
     * @param bool $setMigratedVersionAfter Whether we should clear the migration version value after rolling back from zero.
     * @return void
     */
    public function migrateFromZero($setMigratedVersionAfter = true)
    {
        $this->runPrePostMethods = false;
        $this->ignoreUpperConstraint = true;
        $this->migrationDirection = 'up';
        if ($migrations = $this->resolveMigrations()) {
            foreach ($migrations as $migration) {
                $this->runMigration($migration);
            }
        }
        if ($setMigratedVersionAfter) {
            $this->updateMigratedVersion();
        }
    }

    /**
     * Rollback from current migration to zero, effectively deleting all traces of the plugin in the database.
     *
     * @param bool $clearMigratedVersionAfter Whether we should clear the migration version value after rolling back to zero.
     * @return void
     */
    public function rollbackToZero($clearMigratedVersionAfter = true)
    {
        $this->runPrePostMethods = false;
        $this->ignoreLowerConstraint = true;
        $this->migrationDirection = 'down';
        if ($migrations = $this->resolveMigrations()) {
            foreach ($migrations as $migration) {
                $this->runMigration($migration);
            }
        }
        if ($clearMigratedVersionAfter) {
            $this->clearMigratedVersion();
        }
    }

    private function setCurrentVersions(): void
    {
        $this->currentMigratedVersion = $this->getCurrentMigratedVersion();
        $this->currentPluginVersion = getCurrentPluginVersion();
    }

    public function maybeRunMigrations(): void
    {
        $this->setCurrentVersions();
        if ($this->currentMigratedVersion !== $this->currentPluginVersion) {
            $this->doMigration();
        }
    }

    /**
     * Check that all tables exist.
     *
     * @return bool
     */
    public function tablesExist(): bool
    {
        $this->ignoreUpperConstraint = true;
        $this->migrationDirection = 'up';
        if ($migrations = $this->resolveMigrations()) {
            foreach ($migrations as $migration) {
                $tableName = (new $migration)->getTableNameWithPrefix();
                if (!tableExists($tableName)) {
                    return false;
                }
            }

        }
        return true;
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
            $className = '\\Servebolt\\Optimizer\\Utils\\DatabaseMigration\\Migrations\\' . $migrationName;
            require_once $migrationFile;
            if ($this->shouldRunMigration($className)) {
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
        $multisiteSupport = is_multisite() && (!property_exists($migrationClass, 'multisiteSupport') || $migrationClass::$multisiteSupport === true);
        if ($multisiteSupport) {
            if ($this->siteId) {
                switch_to_blog($this->siteId);
                $this->runMigrationSteps($migrationClass);
            } else {
                iterateSites(function ($site) use ($migrationClass) {
                    switch_to_blog($site->blog_id);
                    $this->runMigrationSteps($migrationClass);
                });
            }
            restore_current_blog();
        } else {
            $this->runMigrationSteps($migrationClass);
        }
    }

    /**
     * Check if migration is already executed.
     *
     * @param $instance
     * @param $migrationMethod
     * @return bool
     */
    private function alreadyCompleted($instance, $migrationMethod): bool
    {
        return (
            method_exists($instance, 'hasBeenRun')
            && (
                (
                    $migrationMethod == 'up'
                    && $instance->hasBeenRun() === true
                ) ||
                (
                    $migrationMethod == 'down'
                    && $instance->hasBeenRun() === false
                )
            )
        );
    }

    /**
     * Run migration steps.
     *
     * @param $migrationClass
     */
    private function runMigrationSteps($migrationClass): void
    {
        $instance = new $migrationClass;
        $migrationMethod = $this->migrationDirection;
        if ($this->alreadyCompleted($instance, $migrationMethod)) {
            return; // Skip it, this migration is already completed
        }
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
     * @param string $className
     * @return bool
     */
    private function shouldRunMigration(string $className): bool
    {
        $isActive = !property_exists($className, 'active') || $className::$active === true;
        if (!$isActive) {
            return false; // Migration not active
        }

        $migrationVersion = $className::$version;
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
            return true; // We should migrate down using this migration
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
            return true; // We should migrate up using this migration
        } else {
            return false; // We should not run this migration
        }
    }

    private function getMigrationDirection(): string
    {
        return version_compare($this->currentMigratedVersion, $this->currentPluginVersion, '<') ? 'up' : 'down';
    }

    public function updateMigratedVersion($version = null): void
    {
        if (!$version) {
            $version = getCurrentPluginVersion();
        }
        $this->currentMigratedVersion =  $version;
        if (is_multisite()) {
            updateSiteOption($this->migrationVersionOptionsKey(), $version);
        } else {
            updateOption($this->migrationVersionOptionsKey(), $version);
        }
    }

    public function clearMigratedVersion(): void
    {
        $this->currentMigratedVersion =  null;
        if (is_multisite()) {
            deleteSiteOption($this->migrationVersionOptionsKey());
        } else {
            deleteOption($this->migrationVersionOptionsKey());
        }
    }

    public function getCurrentMigratedVersion(): ?string
    {
        if (is_multisite()) {
            return getSiteOption($this->migrationVersionOptionsKey());
        } else {
            return getOption($this->migrationVersionOptionsKey());
        }
    }
}
