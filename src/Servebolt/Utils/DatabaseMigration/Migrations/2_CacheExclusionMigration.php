<?php

namespace Servebolt\Optimizer\Utils\DatabaseMigration\Migrations;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Utils\DatabaseMigration\AbstractMigration;
use Servebolt\Optimizer\Utils\DatabaseMigration\MigrationInterface;
use function Servebolt\Optimizer\Helpers\tableExists;

/**
 * Class CacheExclusionMigration
 * @package Servebolt\Optimizer\Utils\DatabaseMigration\Migrations
 */
class CacheExclusionMigration extends AbstractMigration implements MigrationInterface
{

    /**
     * @var bool Whether the migration is active (optional, defaults to true if omitted).
     */
    public static $active = false;

    /**
     * @var string Table name (optional).
     */
    protected $tableName = 'sb_cache_exception';

    /**
     * @var string The plugin version number that this migration belongs to.
     */
    public static $version = '2.2.0';

    /**
     * Migrate up.
     */
    public function up(): void
    {
        $sql = <<<EOF
CREATE TABLE IF NOT EXISTS `%table-name%` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `url` varchar(255) NOT NULL DEFAULT '',
  `object_type` varchar(25) NOT NULL DEFAULT 'post',
  `object_id` bigint(20) unsigned NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=%storage-engine% %charset-collate%;
EOF;
        $this->runSql($sql);
    }

    /**
     * Check whether the table exists.
     *
     * @return bool
     */
    public function hasBeenRun($migrationMethod): bool
    {
        switch($migrationMethod) {
            case 'up':
                // if Table does exit return TRUE, to true processing.
                return (tableExists($this->getTableNameWithPrefix())) ? true : false; 
                break;
            case 'down':
                // if Table does exit return FALSE, to continue processing.
                return (tableExists($this->getTableNameWithPrefix())) ? false : true; 
                break;
        }
        
    }

    /**
     * Migrate down.
     */
    public function down(): void
    {
        $this->dropTable();
    }
}
