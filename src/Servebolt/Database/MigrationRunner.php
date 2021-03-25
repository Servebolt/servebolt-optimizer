<?php

namespace Servebolt\Optimizer\Database;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

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
     * Migration constructor.
     */
    public function __construct()
    {
        add_action('admin_init', [$this, 'checkIfWeShouldMigrate']);
    }

    public static function run(): void
    {
        new self;
    }

    public function checkIfWeShouldMigrate(): void
    {
        $this->currentMigratedVersion = $this->getCurrentMigratedVersion();
        $this->currentPluginVersion = $this->getCurrentPluginVersion();
        if ($this->currentMigratedVersion !== $this->currentPluginVersion) {
            $this->migrate();
        }
    }

    private function migrate(): void
    {
        $this->migrationDirection = $this->getMigrationDirection();
        if ($migrations = $this->resolveMigrations()) {
            foreach ($migrations as $migration) {
                $this->runMigration($migration);
            }
        }
        $this->updateMigratedVersion();
        var_dump(count($migrations));die;
    }

    private function resolveMigrations(): array
    {
        $migrationFiles = glob(__DIR__ . '/Migrations/*.php');
        $migrations = [];
        foreach($migrationFiles as $migrationFile) {
            $fileNameParts = explode('_', basename($migrationFile, '.php'));
            if (count($fileNameParts) !== 2) {
                continue;
            }
            list($migrationNumber, $migrationName) = $fileNameParts;
            $className = '\\Servebolt\\Optimizer\\Database\\Migrations\\' . $migrationName;
            require $migrationFile;
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

    private function runMigration(string $migration): void
    {
        $instance = new $migration;
        $migrationMethod = $this->migrationDirection;
        $preMethod = 'pre' . ucfirst($migrationMethod);
        $postMethod = 'post' . ucfirst($migrationMethod);
        if (method_exists($instance, $migrationMethod)) {
            if (method_exists($instance, 'pre')) {
                $instance->pre();
            }
            if (method_exists($instance, $preMethod)) {
                $instance->{$preMethod}();
            }
            $instance->{$migrationMethod}();
            if (method_exists($instance, 'post')) {
                $instance->post();
            }
            if (method_exists($instance, $preMethod)) {
                $instance->{$postMethod}();
            }
        }
    }

    private function shouldRunMigration($migrationVersion): bool
    {
        if (
            $this->migrationDirection == 'down'
            && $migrationVersion > $this->currentPluginVersion
            && $migrationVersion <= $this->currentMigratedVersion
        ) {
            return true;
        } elseif (
            $this->migrationDirection == 'up'
            && $migrationVersion <= $this->currentPluginVersion
            && $migrationVersion > $this->currentMigratedVersion
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
        updateOption('migration_version', $version);
    }

    public function getCurrentMigratedVersion(): string
    {
        return getOption('migration_version');
    }

    public function getCurrentPluginVersion(bool $ignoreBetaVersion = true): string
    {
        return '2.1.3';
        $pluginData = get_plugin_data(SERVEBOLT_PLUGIN_FILE);
        if ($ignoreBetaVersion) {
            return preg_replace('/(.+)-(.+)/', '$1', $pluginData['Version']);
        }
        return $pluginData['Version'];
    }
}
