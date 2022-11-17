<?php

namespace Servebolt\Optimizer\Utils\DatabaseMigration\Migrations;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Utils\DatabaseMigration\AbstractMigration;
use Servebolt\Optimizer\Utils\DatabaseMigration\MigrationInterface;
use function Servebolt\Optimizer\Helpers\tableHasIndex;
use function Servebolt\Optimizer\Helpers\tableExists;

/**
 * Class AddParentIdIndexToQueueTable
 * @package Servebolt\Optimizer\Utils\DatabaseMigration\Migrations
 */
class AddParentIdIndexToQueueTable extends AbstractMigration implements MigrationInterface
{

    /**
     * Whether to use the function "dbDelta" when running queries.
     *
     * @var bool
     */
    protected $useDbDelta = false;

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
     * Check whether the table has the index.
     *
     * @return bool
     */
    public function hasBeenRun($migrationMethod): bool
    {
        switch($migrationMethod) {
            case 'up':
                // if Table does NOT exit return TRUE, to stop processing.
                // if Table exists and index exists return TRUE to stop processing
                if (!tableExists($this->getTableNameWithPrefix()) || ( tableExists($this->getTableNameWithPrefix()) && tableHasIndex($this->getTableNameWithPrefix(), 'parent_id_index'))) {
                    return true;
                } 
                // else return false so that processing can continue;
                return false;

                break;
            case 'down':
                // if Table does NOT exit return TRUE, to stop processing.
                // if Table exists and index does not exist return TRUE to stop processing
                if (!tableExists($this->getTableNameWithPrefix()) || ( tableExists($this->getTableNameWithPrefix()) && !tableHasIndex($this->getTableNameWithPrefix(), 'parent_id_index'))) {
                    return true;
                } 
                // else return false so that processing can continue;
                return false;
                break;
        }
    }

    /**
     * Migrate down.
     */
    public function down(): void
    {
        $this->runSql('ALTER TABLE `%table-name%` DROP INDEX `parent_id_index`');
    }
}
