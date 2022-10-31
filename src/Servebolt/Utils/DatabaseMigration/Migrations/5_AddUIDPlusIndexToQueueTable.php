<?php

namespace Servebolt\Optimizer\Utils\DatabaseMigration\Migrations;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Utils\DatabaseMigration\AbstractMigration;
use Servebolt\Optimizer\Utils\DatabaseMigration\MigrationInterface;
use function Servebolt\Optimizer\Helpers\tableHasIndex;
use function Servebolt\Optimizer\Helpers\tableExists;
use function Servebolt\Optimizer\Helpers\tableHasColumn;

/**
 * Class AddParentIdIndexToQueueTable
 * @package Servebolt\Optimizer\Utils\DatabaseMigration\Migrations
 */
class AddUIDPlusIndexToQueueTable extends AbstractMigration implements MigrationInterface
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
    public static $version = '3.5.10';

    /**
     * Migrate up.
     * 
     * Add new column UID, UID will be used to check if item has already be added to the queue 
     * Add index to new colum
     * 
     * Usind 64chars to be a sha256 hash of the payload so that it can checked for uniqueness.
     */
    public function up(): void
    {
        error_log('trying to run alter');
        $this->runSql('ALTER TABLE `%table-name%` ADD COLUMN `UID` VARCHAR(65), ADD INDEX `uid_index` (`UID`);');
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
                if ( 
                    tableHasColumn($this->getTableNameWithPrefix(), 'UID') && 
                    tableHasIndex($this->getTableNameWithPrefix(), 'uid_index') ) {
                    return true;
                } else {                    
                    return false;
                }
                // else return false so that processing can continue;
                return false;

                break;
            case 'down':
                // if Table does not exists or the index does not exist return TRUE to stop processing
                if (
                    !tableExists($this->getTableNameWithPrefix()) ||
                    (!tableHasColumn($this->getTableNameWithPrefix(), 'UID') && 
                     !tableHasIndex($this->getTableNameWithPrefix(), 'uid_index'))
                ) {
                    return true;
                } 
                // else return false so that processing can continue;
                return false;
                break;
        }
    }

    /**
     * Migrate down.
     * 
     * Drop the column and its index
     */
    public function down(): void
    {
        $this->runSql('ALTER TABLE `%table-name%` DROP COLUMN `UID`, DROP INDEX `uid_index`');
    }
}
