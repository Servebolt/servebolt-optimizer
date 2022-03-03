<?php

namespace Servebolt\Optimizer;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Exception;
use Servebolt\Optimizer\Traits\Singleton;
use Servebolt\Optimizer\Utils\DatabaseMigration\MigrationRunner;
use function Servebolt\Optimizer\Helpers\isAjax;
use function Servebolt\Optimizer\Helpers\isCli;
use function Servebolt\Optimizer\Helpers\isCron;
use function Servebolt\Optimizer\Helpers\isTesting;
use function Servebolt\Optimizer\Helpers\isWpRest;

/**
 * Class WpDatabaseMigrations
 * @package Servebolt\Optimizer
 */
class WpDatabaseMigrations
{
    use Singleton;

    /**
     * Alias for "getInstance".
     */
    public static function init(): void
    {
        self::getInstance();
    }

    /**
     * WpDatabaseMigrations constructor.
     */
    public function __construct()
    {



        // Run up/down migration for new/deleted sites
        if (!isTesting()) {
            add_action('admin_init', [$this, 'multisiteSupport']);
        }

        if (is_admin()) {
            add_action('admin_init', [$this, 'runMigration']);
        } elseif(
            isTesting()
            || isCli()
            || isCron()
            || isAjax()
            || isWpRest()
        ) {
            add_action('init', [$this, 'runMigration']);
        }
    }

    public function runMigration()
    {
        MigrationRunner::run();
    }

    /**
     * Make migrations work in a multisite-network.
     *
     * @return void
     */
    public function multisiteSupport()
    {
        add_action('wp_initialize_site', [$this, 'siteCreation'], 10, 1);
        add_action('wp_uninitialize_site', [$this, 'siteDeletion'], 10, 1);
    }

    /**
     * Run all migrations for newly created site.
     *
     * @param $site
     * @return void
     */
    public function siteCreation($site)
    {
        try {
            MigrationRunner::handleNewSite($site->blog_id);
        } catch (Exception $e) {}
    }

    /**
     * Rollback all migrations for site that is about to be deleted.
     *
     * @param $site
     * @return void
     */
    public function siteDeletion($site)
    {
        try {
            MigrationRunner::handleDeletedSite($site->blog_id);
        } catch (Exception $e) {}
    }
}
