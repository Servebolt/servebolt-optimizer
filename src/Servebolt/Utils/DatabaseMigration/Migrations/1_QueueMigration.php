<?php

namespace Servebolt\Optimizer\Utils\DatabaseMigration\Migrations;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Utils\DatabaseMigration\AbstractMigration;

/**
 * Class QueueMigration
 * @package Servebolt\Optimizer\Utils\DatabaseMigration\Migrations
 */
class QueueMigration extends AbstractMigration
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
    public static $version = '2.2.0';

    /**
     * Migrate up.
     */
    public function up(): void
    {
        $sql = <<<EOF
CREATE TABLE IF NOT EXISTS `%table-name%` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `parent_queue_name` varchar(255) COLLATE %charset-collate% DEFAULT NULL,
  `queue` varchar(255) COLLATE %charset-collate% NOT NULL,
  `payload` longtext COLLATE %charset-collate% NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `force_retry` tinyint(1) unsigned DEFAULT 0,
  `failed_at_gmt` int(10) unsigned DEFAULT NULL,
  `reserved_at_gmt` int(10) unsigned DEFAULT NULL,
  `completed_at_gmt` int(10) unsigned DEFAULT NULL,
  `updated_at_gmt` int(10) unsigned DEFAULT NULL,
  `created_at_gmt` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=%mysql-engine% DEFAULT CHARSET=%charset% COLLATE=%charset-collate%;
EOF;
        $this->runSql($sql);
    }

    public function down(): void
    {
        $this->dropTable();
    }
}
