<?php

namespace Servebolt\Optimizer\Utils\DatabaseMigration\Migrations;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\CachePurge\WordPressCachePurge\WordPressCachePurge;
use Servebolt\Optimizer\Utils\DatabaseMigration\AbstractMigration;
use Servebolt\Optimizer\Utils\DatabaseMigration\MigrationInterface;
use function Servebolt\Optimizer\Helpers\isHostedAtServebolt;
use function Servebolt\Optimizer\Helpers\isAjax;
use function Servebolt\Optimizer\Helpers\isCli;
use function Servebolt\Optimizer\Helpers\isCron;
use function Servebolt\Optimizer\Helpers\isTesting;
use function Servebolt\Optimizer\Helpers\isWpRest;
use function Servebolt\Optimizer\Helpers\smartUpdateOption;
use function Servebolt\Optimizer\Helpers\getOptionName;

/**
 * Class AddParentIdIndexToQueueTable
 * @package Servebolt\Optimizer\Utils\DatabaseMigration\Migrations
 */
class Cache404Control extends AbstractMigration implements MigrationInterface
{
    /**
     * Set a blog suffix for tags.
     *
     * @var string
     */
    protected $blogId = null;
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
    protected $tableName = 'options';

    /**
     * @var string The plugin version number that this migration belongs to.
     * 
     * @since 3.5.11 this is now the db version, number greater than 100
     * @see getCurrentDatabaseVersion() in helpers and const SERVEBOLT_PLUGIN_DB_VERSION
     */
    public static $version = '103';

    protected function setBlog()
    {
        if (is_multisite()) {
            $this->blogId = get_current_blog_id();
        }
    }

    /**
     * Migrate up.
     *
     * cache purge if on servebolt and a ACD/CDN customer.
     */
    public function up(): void
    {
        if (
            isAjax()
            || isCron()
            || isCli()
            || isWpRest()
            || isTesting()
        ) return;

        if(!isHostedAtServebolt()) return;

        $this->setBlog();

        // adding defaults for two new options
        smartUpdateOption($this->blogId, 'cache_404_switch', 1, true);
        smartUpdateOption($this->blogId, 'fast_404_switch', 1, true);

        if(is_multisite()) {
            WordPressCachePurge::purgeAllNetwork();
        } else {
            WordPressCachePurge::purgeAll();
        }
    }

    /**
     * Return false so that it runs. 
     *
     * @return bool
     */
    public function hasBeenRun($migrationMethod): bool
    {        
        switch($migrationMethod) {
            case 'up':
            case 'down':
                return false;
        }
    }

    /**
     * Migrate down.
     * 
     * do nothing, here for interface needs
     */
    public function down(): void
    {
        // 
    }
}
