<?php

namespace Servebolt\Optimizer\Cli\Cache;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use WP_CLI;

/**
 * Class Cache
 * @package Servebolt\Optimizer\Cli\Cache
 */
class Cache
{

    public $setup = Setup::class;

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
        WP_CLI::add_command('servebolt cache-purge setup', [$this->setup, 'cachePurgeSetup']);
return;
        WP_CLI::add_command('servebolt cache-purge status', [$this, 'cachePurge_status']);
        WP_CLI::add_command('servebolt cache-purge activate', [$this, 'cachePurge_enable']);
        WP_CLI::add_command('servebolt cache-purge deactivate', [$this, 'cachePurge_disable']);

        WP_CLI::add_command('servebolt cache-purge auto-purge-content activate', [$this, 'cachePurge_auto_purge_content_enable']);
        WP_CLI::add_command('servebolt cache-purge auto-purge-content deactivate', [$this, 'cachePurge_auto_purge_content_disable']);

        WP_CLI::add_command('servebolt cache-purge driver get', [$this, 'cachePurge_driver_get']);
        WP_CLI::add_command('servebolt cache-purge driver set', [$this, 'cachePurge_driver_set']);

        WP_CLI::add_command('servebolt cache-purge api test', [$this, 'cachePurge_test_api_connection']);
        WP_CLI::add_command('servebolt cache-purge api credentials get', [$this, 'cachePurge_get_credentials']);
        WP_CLI::add_command('servebolt cache-purge api credentials set', [$this, 'cachePurge_set_credentials']);
        WP_CLI::add_command('servebolt cache-purge api credentials clear', [$this, 'cachePurge_clear_credentials']);

        WP_CLI::add_command('servebolt cache-purge cf zone list', [$this, 'cachePurge_cf_list_zones']);
        WP_CLI::add_command('servebolt cache-purge cf zone get', [$this, 'cachePurge_cf_get_zone']);
        WP_CLI::add_command('servebolt cache-purge cf zone set', [$this, 'cachePurge_cf_set_zone']);
        WP_CLI::add_command('servebolt cache-purge cf zone clear', [$this, 'cachePurge_cf_clear_zone']);

        WP_CLI::add_command('servebolt cache-purge config get', [$this, 'cachePurge_cf_get_config']);
        WP_CLI::add_command('servebolt cache-purge config set', [$this, 'cachePurge_cf_set_config']);
        WP_CLI::add_command('servebolt cache-purge config clear', [$this, 'cachePurge_cf_clear_config']);

        //WP_CLI::add_command('servebolt cf purge type', [$this, 'command_cf_set_purge_type']);
        //WP_CLI::add_command('servebolt cf purge status', [$this, 'command_cf_purge_status']);

        //WP_CLI::add_command('servebolt cf purge clear-queue', [$this, 'command_cf_clear_cache_purge_queue']);
        WP_CLI::add_command('servebolt cache-purge purge url', [$this, 'cachePurge_url']);
        WP_CLI::add_command('servebolt cache-purge purge post', [$this, 'cachePurge_post']);
        WP_CLI::add_command('servebolt cache-purge purge all', [$this, 'cachePurge_all']);
    }

    private function registerLegacyCommands(): void
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
        WP_CLI::error('This command is deprecated. Please use "wp servebolt cache-purge" instead.');
    }
}
