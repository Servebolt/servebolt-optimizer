<?php

namespace Servebolt\Optimizer\Database\Migrations;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Database\Migration;

/**
 * Class MigrationTemplate
 *
 * Be sure to name this file "{migration-number}_SomethingMigration.php".
 * "migration-number" should be an incremental number starting at 1 and upwards.
 * Example: 8_SomethingMigration.php
 *
 * @package Servebolt\Optimizer\Database\Migrations
 */
class MigrationTemplate extends Migration
{

    /**
     * @var string Table name (optional).
     */
    protected $tableName = 'table_name';

    /**
     * @var string The plugin version number that this migration belongs to.
     */
    public static $version = '1.0.0';

    /**
     * Migrate up.
     */
    public function up(): void
    {
        $sql = <<<EOF
CREATE TABLE IF NOT EXISTS `%table-name%` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1527 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
EOF;
        $this->runSql($sql);
    }

    /**
     * Migrate down.
     */
    public function down(): void
    {
        $this->dropTable();
    }

    /**
     * Before migrating, regardless of direction.
     */
    public function preMigration()
    {
    }

    /**
     * After migrating, regardless of direction.
     */
    public function postMigration()
    {
    }

    /**
     * Before migrating down.
     */
    public function preDownMigration()
    {
    }

    /**
     * After migrating down.
     */
    public function postDownMigration()
    {
    }

    /**
     * Before migrating up.
     */
    public function preUpMigration()
    {
    }

    /**
     * After migrating up.
     */
    public function postUpMigration()
    {
    }
}
