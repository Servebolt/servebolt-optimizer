<?php

namespace Servebolt\Optimizer;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use function Servebolt\Optimizer\Helpers\isTesting;

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
        } elseif(isTesting()) {
            add_action('init', $method);
        }
    }
}
