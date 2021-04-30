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
     * ## OPTIONS
     *
     * [--dry-run]
     * : Whether to run as a dry run.
     *
     * [--format=<format>]
     * : Return format.
     * ---
     * default: text
     * options:
     *   - text
     *   - json
     * ---
     *
     * ## EXAMPLES
     *
     *     wp servebolt db fix
     *
     */
    public function optimizeDatabaseAlias($args, $assocArgs)
    {
        $this->optimizeDatabase($args, $assocArgs);
    }

    /**
     * Add database indexes and convert database tables to modern table types or delete transients.
     *
     * ## OPTIONS
     *
     * [--dry-run]
     * : Whether to run as a dry run.
     *
     * [--format=<format>]
     * : Return format.
     * ---
     * default: text
     * options:
     *   - text
     *   - json
     * ---
     *
     * ## EXAMPLES
     *
     *     wp servebolt db optimize
     *
     */
    public function optimizeDatabase($args, $assocArgs)
    {
        CliHelpers::setReturnJson($assocArgs);
        $dryRun = array_key_exists('dry-run', $assocArgs);
        $instance = DatabaseOptimizer::getInstance();
        $instance->setDryRun($dryRun)
            ->optimizeDb(true);
    }

    /**
     * Analyze tables.
     *
     * ## OPTIONS
     *
     * [--format=<format>]
     * : Return format.
     * ---
     * default: text
     * options:
     *   - text
     *   - json
     * ---
     *
     * ## EXAMPLES
     *
     *     wp servebolt db analyze
     *
     */
    public function analyzeTables($args, $assocArgs)
    {
        CliHelpers::setReturnJson($assocArgs);
        $instance = DatabaseOptimizer::getInstance();
        if ($instance->analyzeTables(true)) {
            $message = __('Analyzed tables.', 'servebolt-wp');
            if (CliHelpers::returnJson()) {
                CliHelpers::printJson([
                    'success' => true,
                    'message' => $message,
                ]);
            } else {
                WP_CLI::success($message);
            }
        } else {
            $errorMessage = __('Could not analyze tables.', 'servebolt-wp');
            if (CliHelpers::returnJson()) {
                CliHelpers::printJson([
                    'success' => false,
                    'message' => $errorMessage,
                ]);
            } else {
                WP_CLI::error($errorMessage);
            }
        }
    }
}
