<?php

namespace Servebolt\Optimizer\Cli\General;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use WP_CLI;
use Servebolt\Optimizer\Cli\CliHelpers;
use function Servebolt\Optimizer\Helpers\deleteAllSettings;

class General
{

    /**
     * General constructor.
     */
    public function __construct()
    {
        WP_CLI::add_command('servebolt delete-all-settings', [$this, 'commandDeleteAllSettings']);
    }

    /**
     * Delete all settings related to this plugin.
     *
     * ## OPTIONS
     *
     * [--all]
     * : Delete all settings on all sites in multisite-network.
     *
     * ## EXAMPLES
     *
     *     wp servebolt delete-all-settings
     */
    public function commandDeleteAllSettings($args, $assocArgs)
    {
        if ($affectAllSites = CliHelpers::affectAllSites($assocArgs)) {
            WP_CLI::confirm(__('Do you really want to delete all settings? This will affect all sites in multisite-network.', 'servebolt-wp'));
        } else {
            WP_CLI::confirm(__('Do you really want to delete all settings?', 'servebolt-wp'));
        }
        deleteAllSettings($affectAllSites);
        WP_CLI::success(__('All settings deleted!', 'servebolt-wp'));
    }
}
