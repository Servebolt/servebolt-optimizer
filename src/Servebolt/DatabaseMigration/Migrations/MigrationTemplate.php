<?php

namespace Servebolt\Optimizer\DatabaseMigration\Migrations;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\DatabaseMigration\AbstractMigration;

/**
 * Class MigrationTemplate
 *
 * Be sure to name this file "{migration-number}_SomethingMigration.php".
 * "migration-number" should be an incremental number starting at 1 and upwards.
 * Example: 8_SomethingMigration.php
 *
 * @package Servebolt\Optimizer\DatabaseMigration\Migrations
 */
class MigrationTemplate extends AbstractMigration
{

    /**
     * @var bool Whether the migration should be executed for each blog in a multisite (optional, defaults to true if omitted).
     */
    public static $multisiteSupport = true;

    /**
     * @var bool Whether the migration is active (optional, defaults to true if omitted).
     */
    public static $active = true;

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
) ENGINE=%mysql-engine% DEFAULT CHARSET=%charset% COLLATE=%charset-collate%;
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
