<?php

namespace Servebolt\Optimizer\Utils\DatabaseMigration;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Interface MigrationInterface
 * @package Servebolt\Optimizer\Utils\DatabaseMigration
 */
interface MigrationInterface {

    /**
     * Check whether migration is already executed.
     *
     * @param $migrationMethod string
     * @return bool|null
     */
    public function hasBeenRun(String $migrationMethod);

    /**
     * Migrate up.
     *
     * @return void
     */
    public function up(): void;

    /**
     * Migrate down.
     *
     * @return void
     */
    public function down(): void;
}
