<?php

namespace Servebolt\Optimizer\Utils\DatabaseMigration\Migrations;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Utils\DatabaseMigration\AbstractMigration;

/**
 * Class AddParentIdIndexToQueueTable
 * @package Servebolt\Optimizer\Utils\DatabaseMigration\Migrations
 */
class AddParentIdIndexToQueueTable extends AbstractMigration
{

    /**
     * @var bool Whether the migration is active (optional, defaults to true if omitted).
     */
    public static $active = true;

    /**
     * @var string Table name (optional).
     */
    protected $tableName = 'sb_queue';

    /**
     * @var string The plugin version number that this migration belongs to.
     */
    public static $version = '3.1.1';

    /**
     * Migrate up.
     */
    public function up(): void
    {
        $this->runSql('ALTER TABLE `%table-name%` ADD INDEX `parent_id_index` (`parent_id`);');
    }

    /**
     * Migrate down.
     */
    public function down(): void
    {
        $this->runSql('ALTER TABLE `%table-name%` DROP INDEX `parent_id_index`');
    }
}
