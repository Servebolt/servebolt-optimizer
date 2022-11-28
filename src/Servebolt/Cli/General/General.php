<?php

namespace Servebolt\Optimizer\Cli\General;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use WP_CLI;
use Servebolt\Optimizer\Cli\CliHelpers;
use Servebolt\Optimizer\Utils\DatabaseMigration\MigrationRunner;
use function Servebolt\Optimizer\Helpers\deleteAllSettings;
use function Servebolt\Optimizer\Helpers\checkDomainIsSetupForServeboltCDN;

class General
{

    /**
     * General constructor.
     */
    public function __construct()
    {
        WP_CLI::add_command('servebolt delete-all-settings', [$this, 'commandDeleteAllSettings']);
        WP_CLI::add_command('servebolt check-db-migrations', [$this, 'checkDatabaseMigrations']);
        WP_CLI::add_command('servebolt check-cdn-setup', [$this, 'checkCdnSetup']);
    }

    /**
     * Delete all settings related to this plugin.
     *
     * ## OPTIONS
     *
     * [--all]
     * : Delete all settings on all sites in multisite-network.
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
     *     wp servebolt delete-all-settings
     */
    public function commandDeleteAllSettings($args, $assocArgs)
    {
        CliHelpers::setReturnJson($assocArgs);
        $affectAllSites = CliHelpers::affectAllSites($assocArgs);
        if (!CliHelpers::returnJson()) { // Skip confirmation when returning JSON
            if ($affectAllSites) {
                WP_CLI::confirm(__('Do you really want to delete all settings? This will affect all sites in multi-site network.', 'servebolt-wp'));
            } else {
                WP_CLI::confirm(__('Do you really want to delete all settings?', 'servebolt-wp'));
            }
        }
        deleteAllSettings($affectAllSites);
        $message = __('All settings deleted!', 'servebolt-wp');
        if (CliHelpers::returnJson()) {
            CliHelpers::printJson([
                'success' => true,
                'message' => $message,
            ]);
        } else {
            WP_CLI::success($message);
        }
    }

    /**
     *  Re-run all migrations (will affect all sites if ran in a Multisite network). This will potentially fix missing tables and other out-of-date database related errors. Only run this if you know what you're doing!
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
     *     wp servebolt check-db-migrations
     */
    public function checkDatabaseMigrations($args, $assocArgs)
    {
        CliHelpers::setReturnJson($assocArgs);
        MigrationRunner::remigrate();
        $message = __('Successfully ran re-migration.', 'servebolt-wp');
        if (CliHelpers::returnJson()) {
            CliHelpers::printJson([
                'success' => true,
                'message' => $message,
            ]);
        } else {
            WP_CLI::success($message);
        }
    }

    /**
     * Checks if Servbolt CDN or Accelerated Domains is correclty configured via A record or CNAME.
     */
    public function checkCdnSetup($args, $assocArgs)
    {
        CliHelpers::setReturnJson($assocArgs);
        $response = checkDomainIsSetupForServeboltCDN();
        $message = "The domain " . $response['host'] ." is *not* configured for Servebolt CDN or Accelerated Domains";
        if($response['status'] == true){
            if($response['cname'] == true){
                $message = "The domain " . $response['host'] ." is corrently configured with a CNAME";
            }
            if($response['a-record'] == true){
                $message = "The domain " . $response['host'] ." is corrently configured with a A Record";
            }
        }

        if (CliHelpers::returnJson()) {
            CliHelpers::printJson([
                'status' => $response['status'],
                'message' => $message,
            ]);
        } else {
           ($response['status']) ? WP_CLI::success($message) : WP_CLI::error($message);
        }
    }
}
