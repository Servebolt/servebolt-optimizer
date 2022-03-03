<?php

namespace Servebolt\Optimizer\Utils\DatabaseMigration;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use function Servebolt\Optimizer\Helpers\getCurrentPluginVersion;
use function Servebolt\Optimizer\Helpers\iterateSites;
use function Servebolt\Optimizer\Helpers\runForSite;
use function Servebolt\Optimizer\Helpers\tableExists;
use function Servebolt\Optimizer\Helpers\getSiteOption;
use function Servebolt\Optimizer\Helpers\getOption;
use function Servebolt\Optimizer\Helpers\deleteOption;
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
     * @var bool Whether the migration runs for a new site.
     */
    private $newSite = false;

    /**
     * @var bool Whether to respect upper version constraint when selecting migrations.
     */
    private $ignoreUpperConstraint = false;

    /**
     * @var bool Whether to run pre and post methods on migration.
     */
    private $runPreAndPostMethods = true;

    /**
     * @var string The options key used to store migration version.
     */
    private $migrationVersionOptionsKey = 'migration_version';

    /**
     * @var null|int The Id of the blog we want to interact with.
     */
    private $blogId;

    public function __construct()
    {
        $this->setCurrentPluginVersion();
    }

    /**
     * Run migration if there are any un-run migrations that fit with the constraint of the current version of the plugin.
     */
    public static function run(): void
    {
        (new self)->runAvailableMigrations();
    }

    /**
     * Will roll all migration back to zero, then forward again to the latest migration.
     */
    public static function refresh(): void
    {
        (new self)->rollbackToZero();
        (new self)->migrateFromZero();
    }

    /**
     * Will roll migrations from zero & forward to the constraint of the current version of the plugin, potentially re-running migrations.
     */
    public static function remigrate(): void
    {
        (new self)->migrateFromZero();
    }

    /**
     * Will roll all migration back to zero, effectively deleting the migration-related traces of the plugin in the database.
     */
    public static function cleanup(): void
    {
        (new self)->rollbackToZero();
    }

    private function setCurrentPluginVersion(): void
    {
        $this->currentPluginVersion = getCurrentPluginVersion(false);
    }

    private function setCurrentMigratedVersion(): void
    {
        $this->currentMigratedVersion = $this->getMigratedVersion();
    }

    private function resolveAndRunMigrations(): void
    {
        if ($migrationClasses = $this->resolveMigrations()) {
            foreach ($migrationClasses as $migrationClass) {
                $this->runMigration($migrationClass);
            }
        }
    }

    /**
     * Run migration by class.
     *
     * @param $migrationClass
     */
    private function runMigration($migrationClass): void
    {
        $instance = new $migrationClass;
        $migrationMethod = $this->migrationDirection;
        if ($this->alreadyCompleted($instance, $migrationMethod)) {
            return; // Skip it, this migration is already completed
        }
        if (method_exists($instance, $migrationMethod)) {
            $this->runPreOrPostMigration('pre', $instance, $migrationMethod);
            $instance->{$migrationMethod}(); // Run the actual migration
            $this->runPreOrPostMigration('post', $instance, $migrationMethod);
        }
    }

    public function runAvailableMigrations(): void
    {
        $this->executeMigrationStepsWithMultisiteSupport(function() {
            $this->checkCurrentMigrationStateAndRunAvailableMigrations();
        });
    }

    public function checkCurrentMigrationStateAndRunAvailableMigrations(): void
    {
        $this->setCurrentMigratedVersion();
        if ($this->currentMigratedVersion !== $this->currentPluginVersion) {
            $this->migrationDirection = $this->getMigrationDirection();
            $this->resolveAndRunMigrations();
            $this->setNewMigratedVersion();
        }
    }

    /**
     * Rollback from current migration to zero, effectively deleting all traces of the plugin in the database.
     *
     * @return void
     */
    public function rollbackToZero(): void
    {
        $this->runPreAndPostMethods = false;
        $this->ignoreLowerConstraint = true;
        $this->migrationDirection = 'down';
        $this->executeMigrationStepsWithMultisiteSupport(function() {
            $this->resolveAndRunMigrations();
            $this->clearMigratedVersion();
        });
    }

    /**
     * Running all migrations from zero to the latest migration.
     *
     * @return void
     */
    public function migrateFromZero()
    {
        $this->runPreAndPostMethods = false;
        $this->ignoreUpperConstraint = true;
        $this->migrationDirection = 'up';
        $this->executeMigrationStepsWithMultisiteSupport(function() {
            $this->resolveAndRunMigrations();
            $this->setNewMigratedVersion();
        });
    }

    /**
     * Run migration but with multisite-support.
     *
     * @param $function
     * @return void
     */
    public function executeMigrationStepsWithMultisiteSupport($function)
    {
        if (is_multisite()) {
            if ($this->blogId) {
                runForSite(function ($site) use ($function) {
                    $this->ensureBlogInheritsMigratedVersionFromSite();
                    $function(); // Single site in multisite-network
                });
            } else {
                iterateSites(function ($site) use ($function) {
                    $this->ensureBlogInheritsMigratedVersionFromSite();
                    $function(); // All sites in multisite-network
                }, true);
            }
        } else {
            $function(); // Single site
        }
    }

    /**
     * Ensure that blog has own value of migrated version inherited from site-options.
     * Previously we stored the migration version in the site-settings for multisites, but the migration version should be defined on blog level.
     *
     * @return void
     */
    private function ensureBlogInheritsMigratedVersionFromSite(): void
    {
        if ($this->newSite) {
            return; // A new site will not have a current migrated version number
        }
        if (!$this->currentMigratedVersion) {
            $siteOption = getSiteOption($this->migrationVersionOptionsKey());
            if ($siteOption) {
                $this->currentMigratedVersion = $siteOption;
            }
        }
    }

    private function migrationVersionOptionsKey(): string
    {
        return apply_filters('sb_optimizer_migration_version_options_key', $this->migrationVersionOptionsKey);
    }

    /**
     * If a site is created we need to run all our migrations for that site.
     *
     * @param $blogId
     * @return void
     */
    public static function handleNewSite($blogId): void
    {
        $instance = new self;
        $instance->blogId = $blogId;
        $instance->newSite = true;
        $instance->migrateFromZero();
    }

    /**
     * If a site is deleted we need to roll back all our migrations for that site.
     *
     * @param $blogId
     * @return void
     */
    public static function handleDeletedSite($blogId): void
    {
        $instance = new self;
        $instance->blogId = $blogId;
        $instance->rollbackToZero();
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
                if (!$tableName) {
                    return true; // This migration does not have a specific table
                }
                if (!tableExists($tableName)) {
                    return false;
                }
            }

        }
        return true;
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

    private function runPreOrPostMigration($preOrPost, $instance, $migrationMethod)
    {
        if ($this->runPreAndPostMethods) {
            // General pre/post migration
            $method = $preOrPost . 'Migration';
            if (method_exists($instance, $method)) {
                $instance->{$method}();
            }

            // Direction-specific pre/post migration
            $method = $preOrPost . ucfirst($migrationMethod) . 'Migration';
            if (method_exists($instance, $method)) {
                $instance->{$method}();
            }
        }
    }

    /**
     * Check if a migration is eligible for down-migration in current migration.
     *
     * @param $migrationVersion string The version that the migration should be applied to.
     * @param $fromVersion string The version we are migrating from.
     * @param $toVersion string The version we want to migrate to.
     * @return bool
     */
    public static function eligibleForDownMigration($migrationVersion, $fromVersion, $toVersion): bool
    {
        if (
            version_compare($migrationVersion, $fromVersion, '<')
            && version_compare($migrationVersion, $toVersion, '>=')
        ) {
            return true;
        }
        return false;
    }

    /**
     * Check if a migration is eligible for up-migration in current migration.
     *
     * @param $migrationVersion string The version that the migration should be applied to.
     * @param $fromVersion string The version we are migrating from.
     * @param $toVersion string The version we want to migrate to.
     * @return bool
     */
    public static function eligibleForUpMigration($migrationVersion, $fromVersion, $toVersion): bool
    {
        if (
            version_compare($migrationVersion, $toVersion, '<=')
            && version_compare($migrationVersion, $fromVersion, '>')
        )
        {
            return true;
        }
        return false;
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
                || self::eligibleForDownMigration($migrationVersion, $this->currentMigratedVersion, $this->currentPluginVersion)
            )
        ) {
            return true; // We should migrate down using this migration
        }

        if (
            $this->migrationDirection == 'up'
            && (
                $this->ignoreUpperConstraint
                || self::eligibleForUpMigration($migrationVersion, $this->currentMigratedVersion, $this->currentPluginVersion)
            )
        ) {
            return true; // We should migrate up using this migration
        }

        return false; // We should not run this migration
    }

    private function getMigrationDirection(): string
    {
        return version_compare($this->currentMigratedVersion, $this->currentPluginVersion, '<') ? 'up' : 'down';
    }

    public function getMigratedVersion(): ?string
    {
        return getOption($this->migrationVersionOptionsKey());
    }

    public function setNewMigratedVersion(): void
    {
        $this->currentMigratedVersion = getCurrentPluginVersion(false);
        updateOption($this->migrationVersionOptionsKey(), $this->currentMigratedVersion);
    }

    public function clearMigratedVersion(): void
    {
        $this->currentMigratedVersion = null;
        deleteOption($this->migrationVersionOptionsKey());
    }
}
