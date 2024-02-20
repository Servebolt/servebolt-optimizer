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
use function Servebolt\Optimizer\Helpers\smartGetOption;

/**
 * Class AddParentIdIndexToQueueTable
 * @package Servebolt\Optimizer\Utils\DatabaseMigration\Migrations
 */
class ForceCachePurge extends AbstractMigration implements MigrationInterface
{
    /**
     * Set a blog suffix for tags.
     *
     * @var string
     */
    protected $blogId = '';
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
     * 
     * @since 3.5.11 this is now the db version, number greater than 100
     * @see getCurrentDatabaseVersion() in helpers and const SERVEBOLT_PLUGIN_DB_VERSION
     */
    public static $version = '102';

    /**
     * Driver
     * 
     * @var string
     */
    protected $driver = '';

    /**
     * Drivers that require the site to be hosted at Servebolt.
     *
     * @var string[]
     */
    protected static $serveboltOnlyDrivers = ['acd', 'serveboltcdn'];

    /**
     * Valid drivers.
     *
     * @var string[]
     */
    protected static $validDrivers = ['cloudflare', 'acd', 'serveboltcdn'];


    /**
     * Get default driver name.
     *
     * @param bool $verbose
     * @return string
     */
    private static function defaultDriverName(bool $verbose = false): string
    {
        return $verbose ? 'Cloudflare' : 'cloudflare';
    }

    protected function setBlog()
    {
        if (is_multisite()) {
            $this->blogId = get_current_blog_id();
        }
    }
 	/**
     * Get the selected cache purge driver.
     *
     * @param int|null $blogId
     * @param bool $strict
     * @return string
     */
    public static function getSelectedCachePurgeDriver(?int $blogId = null, bool $strict = true)
    {
        $defaultDriver = self::defaultDriverName();
        $driver = (string) apply_filters(
            'sb_optimizer_selected_cache_purge_driver',
            smartGetOption(
                $blogId,
                'cache_purge_driver',
                $defaultDriver
            )
        );
        if (!in_array($driver, self::$validDrivers)) {
            $driver = $defaultDriver;
        } else if ($strict && !isHostedAtServebolt() && in_array($driver, self::$serveboltOnlyDrivers)) {
            $driver = $defaultDriver;
        }
        return $driver;
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

        $this->driver = $this->getSelectedCachePurgeDriver(null);

        if($this->driver == 'cloudflare') return;
        
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
