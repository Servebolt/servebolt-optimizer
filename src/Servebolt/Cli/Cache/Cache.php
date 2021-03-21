<?php

namespace Servebolt\Optimizer\Cli\Cache;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use WP_CLI;
use Servebolt\Optimizer\Cli\CliHelpers;

/**
 * Class Cache
 * @package Servebolt\Optimizer\Cli\Cache
 */
class Cache extends CliHelpers
{
    /**
     * Cache constructor.
     */
    public function __construct()
    {
        $this->registerCommands();
        $this->registerLegacyCommands();
    }

    private function registerCommands(): void
    {
        WP_CLI::add_command('servebolt cache-purge setup', [$this, 'command_cache_purge_setup']);

        WP_CLI::add_command('servebolt cache-purge status', [$this, 'command_cache_purge_status']);
        WP_CLI::add_command('servebolt cache-purge activate', [$this, 'command_cache_purge_enable']);
        WP_CLI::add_command('servebolt cache-purge deactivate', [$this, 'command_cache_purge_disable']);

        WP_CLI::add_command('servebolt cache-purge auto-purge-content activate', [$this, 'command_cache_purge_auto_purge_content_enable']);
        WP_CLI::add_command('servebolt cache-purge auto-purge-content deactivate', [$this, 'command_cache_purge_auto_purge_content_disable']);

        WP_CLI::add_command('servebolt cache-purge driver get', [$this, 'command_cache_purge_driver_get']);
        WP_CLI::add_command('servebolt cache-purge driver set', [$this, 'command_cache_purge_driver_set']);

        WP_CLI::add_command('servebolt cache-purge api test', [$this, 'command_cache_purge_test_api_connection']);
        WP_CLI::add_command('servebolt cache-purge api credentials get', [$this, 'command_cache_purge_get_credentials']);
        WP_CLI::add_command('servebolt cache-purge api credentials set', [$this, 'command_cache_purge_set_credentials']);
        WP_CLI::add_command('servebolt cache-purge api credentials clear', [$this, 'command_cache_purge_clear_credentials']);

        WP_CLI::add_command('servebolt cache-purge cf zone list', [$this, 'command_cache_purge_cf_list_zones']);
        WP_CLI::add_command('servebolt cache-purge cf zone get', [$this, 'command_cache_purge_cf_get_zone']);
        WP_CLI::add_command('servebolt cache-purge cf zone set', [$this, 'command_cache_purge_cf_set_zone']);
        WP_CLI::add_command('servebolt cache-purge cf zone clear', [$this, 'command_cache_purge_cf_clear_zone']);

        WP_CLI::add_command('servebolt cache-purge config get', [$this, 'command_cache_purge_cf_get_config']);
        WP_CLI::add_command('servebolt cache-purge config set', [$this, 'command_cache_purge_cf_set_config']);
        WP_CLI::add_command('servebolt cache-purge config clear', [$this, 'command_cache_purge_cf_clear_config']);

        //WP_CLI::add_command('servebolt cf purge type', [$this, 'command_cf_set_purge_type']);
        //WP_CLI::add_command('servebolt cf purge status', [$this, 'command_cf_purge_status']);

        //WP_CLI::add_command('servebolt cf purge clear-queue', [$this, 'command_cf_clear_cache_purge_queue']);
        WP_CLI::add_command('servebolt cache-purge purge url', [$this, 'command_cache_purge_url']);
        WP_CLI::add_command('servebolt cache-purge purge post', [$this, 'command_cache_purge_post']);
        WP_CLI::add_command('servebolt cache-purge purge all', [$this, 'command_cache_purge_all']);
    }

    private function legacyCommands(): void
    {
        // Cloudflare Cache (legacy)
        WP_CLI::add_command('servebolt cf setup',                   [$this, 'cfLegacyMessage']);

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

    public function cfLegacyMessage(): void
    {
        WP_CLI::line('This command is outdated. Please use "wp servebolt cache-purge" instead.');
    }
}
