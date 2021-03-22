<?php

namespace Servebolt\Optimizer\Cli\Optimizations;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use WP_CLI;
use Servebolt\Optimizer\Cli\CliHelpers;
use Servebolt\Optimizer\DatabaseOptimizer\DatabaseOptimizer;

/**
 * Class Optimizations
 * @package Servebolt\Optimizer\Cli\Optimizations
 */
class Optimizations
{

    /**
     * Optimizations constructor.
     */
    public function __construct()
    {
        WP_CLI::add_command('servebolt db optimize', [$this, 'optimizeDatabase']);
        WP_CLI::add_command('servebolt db fix', [$this, 'optimizeDatabaseAlias']);
        WP_CLI::add_command('servebolt db analyze', [$this, 'analyzeTables']);
    }

    /**
     * Alias of "wp servebolt db optimize". Add database indexes and convert database tables to modern table types or delete transients.
     *
     * ## EXAMPLES
     *
     *     wp servebolt db fix
     */
    public function optimizeDatabaseAlias()
    {
        $this->optimizeDatabase();
    }

    /**
     * Add database indexes and convert database tables to modern table types or delete transients.
     *
     * ## EXAMPLES
     *
     *     wp servebolt db optimize
     *
     */
    public function optimizeDatabase()
    {
        $instance = DatabaseOptimizer::getInstance();
        $instance->optimizeDb(true);
    }

    /**
     * Analyze tables.
     *
     * ## EXAMPLES
     *
     *     wp servebolt db analyze
     *
     */
    public function analyzeTables()
    {
        $instance = DatabaseOptimizer::getInstance();
        if (!$instance->analyzeTables(true)) {
            WP_CLI::error(__('Could not analyze tables.', 'servebolt-wp'));
        } else {
            WP_CLI::success(__('Analyzed tables.', 'servebolt-wp'));
        }
    }
}
