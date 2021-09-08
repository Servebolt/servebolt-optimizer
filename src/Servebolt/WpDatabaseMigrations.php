<?php

namespace Servebolt\Optimizer;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

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
    /**
     * WpDatabaseMigrations constructor.
     */
    public function __construct()
    {
        $method = 'Servebolt\\Optimizer\\Utils\\DatabaseMigration\\MigrationRunner::run';
        if (is_admin()) {
            add_action('admin_init', $method);
        } elseif(
            isTesting()
            || isCli()
            || isCron()
            || isAjax()
            || isWpRest()
        ) {
            add_action('init', $method);
        }
    }
}
