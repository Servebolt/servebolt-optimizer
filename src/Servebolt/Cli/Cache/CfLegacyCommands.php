<?php

namespace Servebolt\Optimizer\Cli\Cache;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use WP_CLI;

/**
 * Class CfLegacyCommands
 * @package Servebolt\Optimizer\Cli\Cache
 */
class CfLegacyCommands
{
    /**
     * CfLegacyCommands constructor.
     */
    public function __construct()
    {
        WP_CLI::add_command('servebolt cf status',                  [$this, 'cfLegacyMessage']);
        WP_CLI::add_command('servebolt cf activate',                [$this, 'cfLegacyMessage']);
        WP_CLI::add_command('servebolt cf deactivate',              [$this, 'cfLegacyMessage']);

        WP_CLI::add_command('servebolt cf config get',              [$this, 'cfLegacyMessage']);
        WP_CLI::add_command('servebolt cf config set',              [$this, 'cfLegacyMessage']);
        WP_CLI::add_command('servebolt cf config clear',            [$this, 'cfLegacyMessage']);

        WP_CLI::add_command('servebolt cf api test',                [$this, 'cfLegacyMessage']);
        WP_CLI::add_command('servebolt cf api credentials get',     [$this, 'cfLegacyMessage']);
        WP_CLI::add_command('servebolt cf api credentials set',     [$this, 'cfLegacyMessage']);
        WP_CLI::add_command('servebolt cf api credentials clear',   [$this, 'cfLegacyMessage']);

        WP_CLI::add_command('servebolt cf zone list',               [$this, 'cfLegacyMessage']);
        WP_CLI::add_command('servebolt cf zone get',                [$this, 'cfLegacyMessage']);
        WP_CLI::add_command('servebolt cf zone set',                [$this, 'cfLegacyMessage']);
        WP_CLI::add_command('servebolt cf zone clear',              [$this, 'cfLegacyMessage']);

        WP_CLI::add_command('servebolt cf purge type',              [$this, 'cfLegacyMessage']);
        WP_CLI::add_command('servebolt cf purge status',            [$this, 'cfLegacyMessage']);

        WP_CLI::add_command('servebolt cf purge queue',             [$this, 'cfLegacyMessage']);
        WP_CLI::add_command('servebolt cf purge clear-queue',       [$this, 'cfLegacyMessage']);
        WP_CLI::add_command('servebolt cf purge url',               [$this, 'cfLegacyMessage']);
        WP_CLI::add_command('servebolt cf purge post',              [$this, 'cfLegacyMessage']);
        WP_CLI::add_command('servebolt cf purge all',               [$this, 'cfLegacyMessage']);
    }

    /**
     * Cloudflare outdated command.
     */
    public function cfLegacyMessage(): void
    {
        WP_CLI::error('This command is deprecated. Please run "wp servebolt cf --help" or "wp servebolt cache --help" to see available commands.');
    }
}
