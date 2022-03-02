<?php

namespace Servebolt\Optimizer\Utils\DatabaseMigration\Migrations;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Utils\DatabaseMigration\AbstractMigration;

/**
 * Class LegacyTransientCleaner
 * @package Servebolt\Optimizer\Utils\DatabaseMigration\Migrations
 */
class LegacyTransientCleaner extends AbstractMigration
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
     * @var string Table name (without prefix) (optional).
     */
    protected $tableName = 'options';

    /**
     * @var string The plugin version number that this migration belongs to.
     */
    public static $version = '3.5.1';

    /**
     * Migrate up.
     */
    public function up(): void
    {
        global $wpdb;
        $transientPatternsToClean = ['sb-menu-cache'];
        foreach ($transientPatternsToClean as $transientPattern) {
            $fullTransientKeyPattern = '_transient_' . $transientPattern;
            $this->runSql($wpdb->prepare('DELETE FROM ' . $wpdb->options . ' WHERE autoload = %s AND option_name LIKE %s', 'yes', $fullTransientKeyPattern . '%'));
        }
    }
}
