<?php

namespace Servebolt\Optimizer\Database\Migrations;

use Servebolt\Optimizer\Database\Migration;

class MigrationTemplate extends Migration
{

    protected $tableName = 'table_name';
    public static $version = '1.0.0';

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

    public function down(): void
    {
        $sql = <<<EOF
DROP TABLE IF EXISTS `%table-name%`
EOF;
        $this->runSql($sql);
    }
}
